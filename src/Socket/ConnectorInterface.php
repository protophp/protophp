<?php

namespace Proto\Socket;

use Evenement\EventEmitterInterface;
use Proto\Broadcast\BroadcastReceiverInterface;
use Proto\ProtoOpt;
use Proto\Session\Exception\SessionException;
use Proto\Session\SessionManagerInterface;
use React\Promise\Promise;

interface ConnectorInterface extends EventEmitterInterface, ProtoOpt
{
    /**
     * ConnectorInterface constructor.
     * @param string $uri
     * @param SessionManagerInterface $sessionManager
     * @param string|null $sessionKey
     * @throws SessionException|\Exception
     */
    public function __construct(string $uri, SessionManagerInterface $sessionManager, string $sessionKey = null);

    /**
     * @param $data
     * @param callable|null $onResponse
     * @param callable|null $onDelivery
     * @return ConnectorInterface|Connector
     */
    public function send($data, callable $onResponse = null, callable $onDelivery = null);

    public function invoke($call, $params = []): Promise;

    public function broadcast(): BroadcastReceiverInterface;

    /**
     * @return ConnectorInterface|Connector
     */
    public function connect();
}