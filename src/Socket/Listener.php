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
use React\Socket\ConnectionInterface;
use React\Socket\Server;

class Listener extends EventEmitter implements ListenerInterface
{
    use OptTrait;

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

    public function __construct($uri, SessionManagerInterface $sessionManager)
    {
        $this->sessionManager = $sessionManager;
        $this->broadcast = new Broadcast();

        // Defaults options
        $this
            ->setOpt(self::DISALLOW_DIRECT_INVOKE, true)
            ->setOpt(self::MAP_INVOKE, []);

        $this->server = new Server($uri, Proto::getLoop(), []);
        $this->server->on('connection', function (ConnectionInterface $conn) {

            $transfer = new PromiseTransfer($conn, $this->sessionManager);
            $transfer->init();

            $transfer->on('established', function (PromiseTransferInterface $transfer, SessionInterface $session) {

                if (!$session->is('CONNECTION')) {

                    // Initial the ProtoConnection
                    $conn = new ProtoConnection(null, $this);

                    // Emit the connection
                    $this->emit('connection', [$conn]);

                    // Add to the session
                    $session->set('CONNECTION', $conn);

                } else
                    $conn = $session->get('CONNECTION');

                // Setup the connection in new transfer
                $conn->setup($transfer, $session, $this);

            });

        });
    }

    public function broadcast(): BroadcastInterface
    {
        return $this->broadcast;
    }


}