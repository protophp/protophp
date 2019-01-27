<?php

namespace Proto;

use Evenement\EventEmitterInterface;
use Proto\Session\SessionManagerInterface;
use React\EventLoop\LoopInterface;

interface ConnectorInterface extends EventEmitterInterface
{
    public function __construct(string $uri, LoopInterface $loop, SessionManagerInterface $sessionManager, string $sessionKey = null, array $options = array());

    public function connect();
}