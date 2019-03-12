<?php

namespace Proto\Socket;

use Evenement\EventEmitter;
use Opt\OptTrait;
use Proto\Broadcast\BroadcastReceiver;
use Proto\Broadcast\BroadcastReceiverInterface;
use Proto\PromiseTransfer\PromiseTransfer;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\Proto;
use Proto\Session\SessionInterface;
use Proto\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
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
     * @var ConnectionInterface
     */
    private $conn;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BroadcastReceiver
     */
    private $broadcast;

    /**
     * @var Proto
     */
    private $proto;
    private $connecting = false;

    public function __construct(Proto $proto)
    {
        $this->proto = $proto;
        $this->logger = $proto->logger;
        $this->sessionManager = isset($proto->overwriteSessionManager) ? $proto->overwriteSessionManager : Proto::getSessionManager();
        $this->session = $this->sessionManager->start($proto->sessionKey);

        // Defaults options
        $this
            ->setOpt(self::DISALLOW_DIRECT_INVOKE, true)
            ->setOpt(self::MAP_INVOKE, []);

        $this->conn = new Connection($this);
        $this->broadcast = new BroadcastReceiver($this->conn);
        $this->connector = new \React\Socket\Connector(Proto::getLoop(), []);
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

        isset($this->logger) && $this->logger->info("[Connector#{$this->proto->name}] Trying to connect to '{$this->proto->uri}'.");
        $this->connecting = true;
        $this->connector->connect($this->proto->uri)
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

                    isset($this->logger) && $this->logger->info("[Connector#{$this->proto->name}] Successfully connected to '{$this->proto->uri}'.");
                });

                $conn->on('close', function () {
                    isset($this->logger) && $this->logger->info("[Connector#{$this->proto->name}] Connection closed.");
                    $this->connect();       // reconnect...
                });

                $conn->on('error', function (\Exception $e) {
                    isset($this->logger) && $this->logger->error("[Connector#{$this->proto->name}] {$e->getMessage()}");
                    $this->emit('error', [$e]);
                });

            })
            ->otherwise(function (\Exception $e) {
                isset($this->logger) && $this->logger->error("[Connector#{$this->proto->name}] {$e->getMessage()}");
                $this->emit('error', [$e]);
                $this->connecting = false;
            });

        return $this;
    }
}