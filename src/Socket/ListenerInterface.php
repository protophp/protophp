<?php

namespace Proto\Socket;

use Evenement\EventEmitterInterface;
use Proto\Broadcast\BroadcastInterface;
use Proto\ProtoOpt;
use Proto\Session\SessionManagerInterface;

interface ListenerInterface extends EventEmitterInterface, ProtoOpt
{
    /**
     * ListenerInterface constructor.
     * @param $uri
     * @param SessionManagerInterface $sessionManager
     * @throws \Exception
     */
    public function __construct($uri, SessionManagerInterface $sessionManager);

    public function broadcast(): BroadcastInterface;
}