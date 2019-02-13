<?php

namespace Proto;

use Evenement\EventEmitterInterface;
use Proto\Session\SessionManagerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

interface ConnectorInterface extends EventEmitterInterface, OptConnectionInterface
{
    public function __construct(string $uri, LoopInterface $loop, SessionManagerInterface $sessionManager, string $sessionKey = null, array $options = array());

    public function send($data, callable $onResponse = null, callable $onDelivery = null);

    public function invoke($call, $params = []): Promise;

    public function connect();
}