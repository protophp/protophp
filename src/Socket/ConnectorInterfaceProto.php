<?php

namespace Proto\Socket;

use Evenement\EventEmitterInterface;
use Proto\ProtoOpt;
use Proto\Session\Exception\SessionException;
use Proto\Session\SessionManagerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

interface ConnectorInterfaceProto extends EventEmitterInterface, ProtoOpt
{
    /**
     * ConnectorInterface constructor.
     * @param string $uri
     * @param LoopInterface $loop
     * @param SessionManagerInterface $sessionManager
     * @param string|null $sessionKey
     * @param array $options
     * @throws SessionException
     */
    public function __construct(string $uri, LoopInterface $loop, SessionManagerInterface $sessionManager, string $sessionKey = null, array $options = array());

    public function send($data, callable $onResponse = null, callable $onDelivery = null);

    public function invoke($call, $params = []): Promise;

    public function connect();
}