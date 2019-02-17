<?php

namespace Proto\Socket;

use Evenement\EventEmitter;
use Opt\OptTrait;
use Proto\Broadcast\BroadcastReceiver;
use Proto\Broadcast\BroadcastReceiverInterface;
use Proto\PromiseTransfer\PromiseTransfer;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\Session\SessionInterface;
use Proto\Session\SessionManagerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Socket\ConnectionInterface;

class Connector extends EventEmitter implements ConnectorInterface
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
    private $conn;

    /**
     * @var BroadcastReceiver
     */
    private $broadcast;
    private $uri;
    private $connecting = false;

    public function __construct(string $uri, LoopInterface $loop, SessionManagerInterface $sessionManager, string $sessionKey = null)
    {
        $this->uri = $uri;
        $this->sessionManager = $sessionManager;
        $this->session = $this->sessionManager->start($sessionKey);

        // Defaults options
        $this
            ->setOpt(self::DISALLOW_DIRECT_INVOKE, true)
            ->setOpt(self::MAP_INVOKE, []);

        $this->conn = new ProtoConnection($this);
        $this->broadcast = new BroadcastReceiver($this->conn);
        $this->connector = new \React\Socket\Connector($loop, []);
    }

    public function send($data, callable $onResponse = null, callable $onDelivery = null)
    {
        $this->conn->send($data, $onResponse, $onDelivery);
        return $this;
    }

    public function invoke($call, $params = []): Promise
    {
        return $this->conn->invoke($call, $params);
    }

    public function broadcast(): BroadcastReceiverInterface
    {
        return $this->broadcast;
    }

    public function connect()
    {
        if ($this->conn->isConnected() || $this->connecting)
            return $this;

        $this->connecting = true;
        $this->connector->connect($this->uri)
            ->then(function (ConnectionInterface $conn) {
                $this->connecting = false;

                $transfer = new PromiseTransfer($conn, $this->sessionManager);
                $transfer->init($this->session);
                $transfer->on('established', function (PromiseTransferInterface $transfer, SessionInterface $session) {

                    if (!$session->is('FIRST_CONNECTION')) {

                        // Initial the ProtoConnection
                        $this->conn->setup($transfer, $session, $this);

                        // Emit the connection
                        $this->emit('connection', [$this->conn]);

                        // Add to the session
                        $session->set('FIRST_CONNECTION', true);

                    } else {
                        // Get ProtoConnection from session
                        $this->conn->setup($transfer, $session, $this);
                    }

                });

                $conn->on('close', function () {
                    $this->connect();       // reconnect...
                });

                $conn->on('error', function (\Exception $e) {
                    $this->emit('error', [$e]);
                });

            })
            ->otherwise(function (\Exception $e) {
                $this->connecting = false;
                $this->emit('error', [$e]);
            });

        return $this;
    }
}