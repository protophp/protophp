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
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use React\Promise\Promise;
use React\Socket\ConnectionInterface;

class Connector extends EventEmitter implements ConnectorInterface
{
    use OptTrait;
    use LoggerTrait;
    use LoggerAwareTrait;

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
            ->setOpt(self::MAP_INVOKE, [])
            ->setOpt(self::RETRY_CONNECTION, 3);

        $this->conn = new Connection($this, null);
        isset($this->logger) && $this->conn->setLogger($this->logger);

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

        $this->info("Trying to connect to '{$this->proto->uri}'.");
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
                        $this->info("The connection established.");

                        // Emit the connection
                        $this->emit('connection', [$this->conn]);

                        // Add to the session
                        $session->set('FIRST_CONNECTION', true);

                    } else {
                        // Get ProtoConnection from session
                        $this->conn->setup($transfer, $session, $this);
                        $this->info("The connection successfully recovered.");
                    }
                });

                $conn->on('close', function () {
                    $this->info("Connection closed.");

                    if (($sec = $this->getOpt(self::RETRY_CONNECTION)) !== false) {

                        $this->info("Retrying connection to '{$this->proto->uri}' after $sec sec...");
                        Proto::getLoop()->addTimer($sec, function () {
                            $this->connect();
                        });
                    }
                });

                $conn->on('error', function (\Exception $e) {
                    $this->error($e->getMessage());
                    $this->emit('error', [$e]);
                });

            })
            ->otherwise(function (\Exception $e) {
                $this->emergency($e->getMessage());
                $this->emit('error', [$e]);
                $this->connecting = false;

                if (($sec = $this->getOpt(self::RETRY_CONNECTION)) !== false) {

                    $this->info("Retrying connection to '{$this->proto->uri}' after $sec sec...");
                    Proto::getLoop()->addTimer($sec, function () {
                        $this->connect();
                    });
                }
            });

        return $this;
    }

    public function log($level, $message, array $context = array())
    {
        isset($this->logger) && $this->logger->log($level, $message, $context);
    }
}