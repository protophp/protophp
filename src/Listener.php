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

    public function __construct($uri, LoopInterface $loop, SessionManagerInterface $sessionManager, array $context = array())
    {
        $this->sessionManager = $sessionManager;

        // Defaults options
        $this
            ->setOpt(self::OPT_DISALLOW_DIRECT_INVOKE, true)
            ->setOpt(self::OPT_MAP_INVOKE, []);

        $this->server = new Server($uri, $loop, $context);
        $this->server->on('connection', function (ConnectionInterface $conn) {

            $transfer = new PromiseTransfer($conn, $this->sessionManager);
            $transfer->init();

            $transfer->on('established', function (PromiseTransferInterface $transfer, SessionInterface $session) {

                if (!$session->is('PROTO-CONN')) {

                    // Initial the ProtoConnection
                    $protoConn = new ProtoConnection();

                    // Emit the connection
                    $this->emit('connection', [$protoConn]);

                    // Add to the session
                    $session->set('PROTO-CONN', $protoConn);

                } else
                    $protoConn = $session->get('PROTO-CONN');

                // Setup the connection in new transfer
                $protoConn->setup($transfer, $session, $this);

            });

        });
    }


}