<?php

namespace Proto\Socket;

use Evenement\EventEmitter;
use Proto\Invoke\InvokeException;
use Proto\Invoke\InvokeParser;
use Proto\Pack\Pack;
use Proto\Pack\PackInterface;
use Proto\PromiseTransfer\ParserInterface;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\ProtoException;
use Proto\ProtoOpt;
use Proto\Session\SessionInterface;
use React\Promise\Deferred;
use React\Promise\Promise;

class ProtoConnection extends EventEmitter implements ProtoConnectionInterface
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
    private $id = null;
    private $hash;

    public function __construct(ConnectorInterface $connector = null, ListenerInterface $listener = null)
    {
        $this->hash = \spl_object_hash($this);
        if (PHP_VERSION_ID >= 70200)
            $this->id = \spl_object_id($this);

        $this->queue = new \SplQueue();

        $this->connector = $connector;
        $this->listener = $listener;
    }

    public function send($data, callable $onResponse = null, callable $onDelivery = null)
    {
        if (!$data instanceof PackInterface) {

            // Security (Remove traces from exceptions)
            if ($data instanceof \Throwable)
                $data = new ProtoException(get_class($data), $data->getMessage(), $data->getCode());

            $data = (new Pack())->setData($data);
        }

        $data->setHeaderByKey(1, self::PROTO_DATA);
        $this->qSend($data, $onResponse, $onDelivery);
    }

    public function invoke($call, $params = []): Promise
    {
        $deferred = new Deferred();

        $pack = (new Pack())->setHeaderByKey(1, self::PROTO_RPC)->setData([$call, $params]);
        $this->qSend($pack, function (PackInterface $pack) use ($deferred) {
            $return = $pack->getData();

            if ($return instanceof ProtoException)
                $deferred->reject($return);
            else
                $deferred->resolve($return);
        });

        return $deferred->promise();
    }

    public function getId(): int
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

    public function setup(PromiseTransferInterface $transfer, SessionInterface $session, ProtoOpt $opt): ProtoConnectionInterface
    {
        $this->transfer = $transfer;
        $this->session = $session;
        $this->opt = $opt;

        // Set transfer events
        $this->setTransferEvents();

        // Flush queue
        while (!$this->queue->isEmpty())
            $this->transfer->send(...$this->queue->dequeue());

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

            switch ($pack->getHeaderByKey(1)) {
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

                default:
                    // TODO

            }
        });

        $this->transfer->on('close', function () {
            $this->transfer = null;
        });
    }
}