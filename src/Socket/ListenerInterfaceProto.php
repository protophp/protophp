<?php

namespace Proto\Socket;

use Evenement\EventEmitterInterface;
use Proto\ProtoOpt;
use Proto\Session\SessionManagerInterface;
use React\EventLoop\LoopInterface;

interface ListenerInterfaceProto extends EventEmitterInterface, ProtoOpt
{
    public function __construct($uri, LoopInterface $loop, SessionManagerInterface $sessionManager, array $context = array());
}