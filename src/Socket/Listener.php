<?php

namespace Proto\Socket;

use Evenement\EventEmitter;
use Opt\OptTrait;
use Proto\Broadcast\Broadcast;
use Proto\Broadcast\BroadcastInterface;
use Proto\PromiseTransfer\PromiseTransfer;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\Proto;
use Proto\Session\SessionInterface;
use Proto\Session\SessionManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use React\Socket\ConnectionInterface;
use React\Socket\Server;

class Listener extends EventEmitter implements ListenerInterface
{
    use OptTrait;
    use LoggerTrait;
    use LoggerAwareTrait;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var Server
     */
    private $server;

    /**
     * @var Broadcast
     */
    private $broadcast;

    /**
     * @var Proto
     */
    private $proto;

    public function __construct(Proto $proto)
    {
        $this->proto = $proto;
        $this->logger = $proto->logger;
        $this->sessionManager = isset($proto->overwriteSessionManager) ? $proto->overwriteSessionManager : Proto::getSessionManager();
        $this->broadcast = new Broadcast();

        // Defaults options
        $this
            ->setOpt(self::DISALLOW_DIRECT_INVOKE, true)
            ->setOpt(self::MAP_INVOKE, []);

        $this->info("Listening on '{$this->proto->uri}'.");
        $this->server = new Server($this->proto->uri, Proto::getLoop(), []);
        $this->server->on('connection', function (ConnectionInterface $conn) {

            $transfer = new PromiseTransfer($conn, $this->sessionManager);
            $transfer->init();

            $transfer->on('established', function (PromiseTransferInterface $transfer, SessionInterface $session) use ($conn) {

                if (!$session->is('CONNECTION')) {

                    $this->info("New connection from '{$conn->getRemoteAddress()}' established.");

                    // Initial the ProtoConnection
                    $connection = new Connection(null, $this);
                    isset($this->logger) && $connection->setLogger($this->logger);

                    // Emit the connection
                    $this->emit('connection', [$connection]);

                    // Add to the session
                    $session->set('CONNECTION', $connection);

                } else {
                    $this->info("The connection recovered from '{$conn->getRemoteAddress()}'.");
                    $connection = $session->get('CONNECTION');
                    $connection->setLogger($this->logger);
                }

                // Setup the connection in new transfer
                $connection->setup($transfer, $session, $this);

            });

        });

        $this->server->on('error', function (\Throwable $e) {
            $this->emergency($e->getMessage());
        });
    }

    public function broadcast(): BroadcastInterface
    {
        return $this->broadcast;
    }

    public function log($level, $message, array $context = array())
    {
        isset($this->logger) && $this->logger->log($level, $message, $context);
    }
}