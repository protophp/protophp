<?php

namespace Proto\Socket;

use Evenement\EventEmitter;
use Proto\Broadcast\Broadcast;
use Proto\Broadcast\BroadcastReceiver;
use Proto\Invoke\InvokeException;
use Proto\Invoke\InvokeParser;
use Proto\Pack\Pack;
use Proto\Pack\PackInterface;
use Proto\PromiseTransfer\ParserInterface;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\Proto;
use Proto\ProtoOpt;
use Proto\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use React\Promise\Deferred;
use React\Promise\Promise;

class Connection extends EventEmitter implements ConnectionInterface
{
    /**
     * @var PromiseTransferInterface
     */
    private $transfer;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var ProtoOpt
     */
    private $opt;

    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     * @var ConnectorInterface
     */
    private $connector;

    /**
     * @var ListenerInterface
     */
    private $listener;

    /**
     * @var Proto
     */
    private $proto;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $isListenerConn;
    private $id = null;
    private $hash;

    public function __construct(ConnectorInterface $connector = null, ListenerInterface $listener = null, Proto $proto = null)
    {
        $this->hash = \spl_object_hash($this);
        if (PHP_VERSION_ID >= 70200)
            $this->id = \spl_object_id($this);

        $this->queue = new \SplQueue();

        $this->proto = $proto;
        $this->logger = $proto->logger;
        $this->connector = $connector;
        $this->listener = $listener;
        $this->isListenerConn = ($listener !== null);
    }

    public function send($data, callable $onResponse = null, callable $onDelivery = null)
    {
        if (!$data instanceof PackInterface)
            $data = Data::data2pack($data);
        else
            $data->setHeaderByKey(PROTO_RESERVED_KEY, self::PROTO_DATA);

        $this->qSend($data, $onResponse, $onDelivery);
    }

    public function invoke($call, $params = []): Promise
    {
        $deferred = new Deferred();

        isset($this->logger) && $this->logger->debug("[Connection#{$this->proto->name}] The RPC call", [$call, $params]);
        $pack = (new Pack())->setHeaderByKey(PROTO_RESERVED_KEY, self::PROTO_RPC)->setData([$call, $params]);
        $this->qSend($pack, function (PackInterface $pack) use ($deferred, $call) {

            isset($this->logger) && $this->logger->debug("[Connection#{$this->proto->name}] The RPC response", [$call]);
            if ($pack->getHeaderByKey(PROTO_RESERVED_KEY) === ConnectionInterface::PROTO_EXCEPTION) {
                list($class, $message, $code) = $pack->getData();
                $deferred->reject(class_exists($class) ? new $class($message, $code) : new \Exception($message, $code));
            } else
                $deferred->resolve($pack->getData());

        });

        return $deferred->promise();
    }

    public function broadcast(PackInterface $pack, callable $onResponse = null, callable $onDelivery = null)
    {
        $pack->setHeaderByKey(PROTO_RESERVED_KEY, self::PROTO_BROADCAST);
        $this->qSend($pack, $onResponse, $onDelivery);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getConnector(): ConnectorInterface
    {
        return $this->connector;
    }

    public function getListener(): ListenerInterface
    {
        return $this->listener;
    }

    public function isConnected(): bool
    {
        return isset($this->transfer);
    }

    public function setup(PromiseTransferInterface $transfer, SessionInterface $session, ProtoOpt $opt): ConnectionInterface
    {
        $this->transfer = $transfer;
        $this->session = $session;
        $this->opt = $opt;

        // Set transfer events
        $this->setTransferEvents();

        // Flush queue
        while (!$this->queue->isEmpty())
            $this->transfer->send(...$this->queue->dequeue());

        isset($this->logger) && $this->logger->info("[Connection#{$this->proto->name}] The connection setup completed.", []);
        return $this;
    }

    private function qSend(PackInterface $pack, callable $onResponse = null, callable $onDelivery = null)
    {
        if ($this->isConnected())
            $this->transfer->send($pack, $onResponse, $onDelivery);
        else
            $this->queue->enqueue([$pack, $onResponse, $onDelivery]);
    }

    private function setTransferEvents()
    {
        $this->transfer->on('data', function (PackInterface $pack, ParserInterface $parser) {
            $data = new Data($pack, $parser, $this->transfer);

            switch ($pack->getHeaderByKey(PROTO_RESERVED_KEY)) {
                case self::PROTO_DATA:
                    $this->emit('data', [$data]);
                    return;

                case self::PROTO_RPC:

                    try {
                        $parser = new InvokeParser($pack, $this->opt);
                    } catch (InvokeException $e) {
                        // Exception response
                        $data->response($e);
                        return;
                    }

                    $call = "{$parser->getClass()}::{$parser->getMethod()}";
                    $params = $parser->getParams();

                    // Call
                    try {
                        $result = $call(...$params);
                    } catch (\Throwable $e) {
                        // Exception response
                        $data->response($e);
                        return;
                    }

                    // On promise result
                    if ($result instanceof Promise) {

                        $result->then(function ($result) use ($data) {

                            $data->response($result);

                        })->otherwise(function ($e) use ($data) {

                            if ($e instanceof \Throwable)
                                $data->response($e);
                            else
                                $data->response(new \Exception("Unknown Error!"));

                        });

                    } else {
                        $data->response($result);
                    }
                    return;

                case self::PROTO_BROADCAST:

                    if ($this->isListenerConn) {
                        /** @var Broadcast $broadcast */
                        $broadcast = $this->listener->broadcast();
                        $broadcast->income($pack, $this);
                    } else {
                        /** @var BroadcastReceiver $broadcast */
                        $broadcast = $this->connector->broadcast();
                        $broadcast->income($pack);
                    }

                    return;

                default:
                    // TODO

            }
        });

        $this->transfer->on('close', function () {
            isset($this->logger) && $this->logger->error("[Connection#{$this->proto->name}] The transfer closed!", []);
            $this->transfer = null;
        });
    }
}