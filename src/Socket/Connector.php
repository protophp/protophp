<?php

namespace Proto\Socket;

use Evenement\EventEmitter;
use Opt\OptTrait;
use Proto\PromiseTransfer\PromiseTransfer;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\Session\SessionInterface;
use Proto\Session\SessionManagerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Socket\ConnectionInterface;

class Connector extends EventEmitter implements ConnectorInterfaceProto
{
    use OptTrait;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var \React\Socket\Connector
     */
    private $connector;

    /**
     * @var ProtoConnectionInterface
     */
    private $protoConn;

    private $dataQueue = [];
    private $invokeQueue = [];
    private $uri;

    public function __construct(string $uri, LoopInterface $loop, SessionManagerInterface $sessionManager, string $sessionKey = null)
    {
        $this->uri = $uri;
        $this->sessionManager = $sessionManager;
        $this->session = $this->sessionManager->start($sessionKey);

        // Defaults options
        $this
            ->setOpt(self::DISALLOW_DIRECT_INVOKE, true)
            ->setOpt(self::MAP_INVOKE, []);

        $this->connector = new \React\Socket\Connector($loop, $options);
    }

    public function send($data, callable $onResponse = null, callable $onDelivery = null)
    {
        if (isset($this->protoConn))
            $this->protoConn->send($data, $onResponse, $onDelivery);
        else
            $this->dataQueue[] = [$data, $onResponse, $onDelivery];
    }

    public function invoke($call, $params = []): Promise
    {
        if (isset($this->protoConn)) {
            return $this->protoConn->invoke($call, $params);
        } else {
            $deferred = new Deferred();
            $this->invokeQueue[] = [$call, $params, $deferred];
            return $deferred->promise();
        }
    }

    public function connect()
    {
        $this->connector->connect($this->uri)
            ->then(function (ConnectionInterface $conn) {

                $transfer = new PromiseTransfer($conn, $this->sessionManager);
                $transfer->init($this->session);
                $transfer->on('established', function (PromiseTransferInterface $transfer, SessionInterface $session) {

                    if (!$session->is('PROTO-CONN')) {

                        // Initial the ProtoConnection
                        $this->protoConn = new ProtoConnection();
                        $this->protoConn->setup($transfer, $session, $this);

                        // Emit the connection
                        $this->emit('connection', [$this->protoConn]);

                        // Add to the session
                        $session->set('PROTO-CONN', $this->protoConn);

                    } else {

                        // Get ProtoConnection from session
                        $this->protoConn = $session->get('PROTO-CONN');
                        $this->protoConn->setup($transfer, $session);

                    }

                    // Flush queue
                    foreach ($this->dataQueue as $params)
                        $this->protoConn->send($params[0], $params[1], $params[2]);

                    foreach ($this->invokeQueue as $invoke)
                        $this->protoConn->invoke($invoke[0], $invoke[1], $invoke[3]);

                    // Clear queue
                    $this->dataQueue = [];
                    $this->invokeQueue = [];
                });

                $conn->on('close', function () {
                    $this->protoConn = null;
                    $this->connect();       // reconnect...
                });

                $conn->on('error', function (\Exception $e) {
                    $this->emit('error', [$e]);
                });

            })
            ->otherwise(function (\Exception $e) {
                $this->emit('error', [$e]);
            });
    }
}