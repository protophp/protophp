<?php

namespace Proto\Socket;

use Evenement\EventEmitterInterface;
use Proto\Broadcast\BroadcastReceiverInterface;
use Proto\ProtoOpt;
use Proto\Session\Exception\SessionException;
use Proto\Session\SessionManagerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

interface ConnectorInterface extends EventEmitterInterface, ProtoOpt
{
    /**
     * ConnectorInterface constructor.
     * @param string $uri
     * @param LoopInterface $loop
     * @param SessionManagerInterface $sessionManager
     * @param string|null $sessionKey
     * @throws SessionException
     */
    public function __construct(string $uri, LoopInterface $loop, SessionManagerInterface $sessionManager, string $sessionKey = null);

    public function send($data, callable $onResponse = null, callable $onDelivery = null): ConnectorInterface;

    public function invoke($call, $params = []): Promise;

    public function broadcast(): BroadcastReceiverInterface;

    public function connect(): ConnectorInterface;
}