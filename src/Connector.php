<?php

namespace Proto;

use Evenement\EventEmitter;
use Opt\OptTrait;
use Proto\PromiseTransfer\PromiseTransfer;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\Session\SessionInterface;
use Proto\Session\SessionManagerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;

class Connector extends EventEmitter implements ConnectorInterface
{
    use OptTrait;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var Session\SessionInterface
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

    private $queue = [];
    private $uri;

    public function __construct(string $uri, LoopInterface $loop, SessionManagerInterface $sessionManager, string $sessionKey = null, array $options = array())
    {
        $this->uri = $uri;
        $this->sessionManager = $sessionManager;
        $this->session = $this->sessionManager->start($sessionKey);
        $this->connector = new \React\Socket\Connector($loop, $options);
    }

    public function send($data, callable $onResponse = null, callable $onDelivery = null)
    {
        if (isset($this->protoConn))
            $this->protoConn->send($data, $onResponse, $onDelivery);
        else
            $this->queue[] = [$data, $onResponse, $onDelivery];
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
                        $this->protoConn->setup($transfer, $session);

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
                    foreach ($this->queue as $params)
                        $this->protoConn->send($params[0], $params[1], $params[2]);

                    // Clear queue
                    $this->queue = [];
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