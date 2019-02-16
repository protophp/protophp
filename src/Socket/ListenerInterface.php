<?php

namespace Proto\Socket;

use Evenement\EventEmitterInterface;
use Proto\Broadcast\BroadcastInterface;
use Proto\ProtoOpt;
use Proto\Session\SessionManagerInterface;
use React\EventLoop\LoopInterface;

interface ListenerInterface extends EventEmitterInterface, ProtoOpt
{
    public function __construct($uri, LoopInterface $loop, SessionManagerInterface $sessionManager);

    public function broadcast(): BroadcastInterface;
}