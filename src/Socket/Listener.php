<?php

namespace Proto\Socket;

use Evenement\EventEmitter;
use Opt\OptTrait;
use Proto\PromiseTransfer\PromiseTransfer;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\Session\SessionInterface;
use Proto\Session\SessionManagerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Server;

class Listener extends EventEmitter implements ListenerInterfaceProto
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

    public function __construct($uri, LoopInterface $loop, SessionManagerInterface $sessionManager)
    {
        $this->sessionManager = $sessionManager;

        // Defaults options
        $this
            ->setOpt(self::DISALLOW_DIRECT_INVOKE, true)
            ->setOpt(self::MAP_INVOKE, []);

        $this->server = new Server($uri, $loop, []);
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