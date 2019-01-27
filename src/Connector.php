<?php

namespace Proto;

use Evenement\EventEmitter;
use Proto\PromiseTransfer\PromiseTransfer;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\Session\SessionInterface;
use Proto\Session\SessionManagerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;

class Connector extends EventEmitter implements ConnectorInterface
{
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

    private $uri;

    public function __construct(string $uri, LoopInterface $loop, SessionManagerInterface $sessionManager, string $sessionKey = null, array $options = array())
    {
        $this->uri = $uri;
        $this->sessionManager = $sessionManager;
        $this->session = $this->sessionManager->start($sessionKey);
        $this->connector = new \React\Socket\Connector($loop, $options);
    }

    public function connect()
    {
        $this->connector->connect($this->uri)->then([$this, 'onConnect'])->otherwise([$this, 'onError']);
    }

    public function onConnect(ConnectionInterface $conn)
    {
        $transfer = new PromiseTransfer($conn, $this->sessionManager);
        $transfer->init($this->session);
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
            $protoConn->setup($transfer, $session);

        });
    }

    public function onError(\Exception $e)
    {
        $this->emit('error', [$e]);
    }
}