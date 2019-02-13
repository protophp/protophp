<?php

namespace Proto;

use Proto\Session\SessionManagerInterface;
use Proto\Socket\Connector;
use Proto\Socket\Listener;
use React\EventLoop\LoopInterface;

interface ProtoInterface
{
    public function connector(string $uri, string $sessionKey = null, SessionManagerInterface $sessionManager = null): Connector;

    public function listener($uri, SessionManagerInterface $sessionManager = null): Listener;

    public static function getLoop(): LoopInterface;

    public static function setup(SessionManagerInterface $sessionManager, LoopInterface $loop);

    public static function getInstance();
}