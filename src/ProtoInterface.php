<?php

namespace Proto;

use Proto\Session\SessionManagerInterface;
use Proto\Socket\Connector;
use Proto\Socket\Listener;
use React\EventLoop\LoopInterface;

interface ProtoInterface
{
    public function uri(string $uri): ProtoInterface;

    public function name($name): ProtoInterface;

    public function sessionKey(string $sessionKey): ProtoInterface;

    public function sessionManager(SessionManagerInterface $sessionManager): ProtoInterface;

    public function connect(): Connector;

    public function listen(): Listener;

    public static function getConnector($name): Connector;

    public static function getListener($name): Listener;

    public static function getLoop(): LoopInterface;

    public static function getSessionManager(): SessionManagerInterface;

    public static function setup(SessionManagerInterface $sessionManager, LoopInterface $loop);
}