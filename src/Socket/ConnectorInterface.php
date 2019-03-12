<?php

namespace Proto\Socket;

use Evenement\EventEmitterInterface;
use Proto\Broadcast\BroadcastReceiverInterface;
use Proto\Proto;
use Proto\ProtoOpt;
use Proto\Session\Exception\SessionException;
use React\Promise\Promise;

interface ConnectorInterface extends EventEmitterInterface, ProtoOpt
{
    /**
     * ConnectorInterface constructor.
     * @param Proto $proto
     * @throws SessionException
     */
    public function __construct(Proto $proto);

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