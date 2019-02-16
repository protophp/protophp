<?php

namespace Proto\Socket;

use Evenement\EventEmitterInterface;
use React\Promise\Promise;

interface ProtoConnectionInterface extends EventEmitterInterface
{
    const PROTO_DATA = 0;
    const PROTO_RPC = 1;
    const PROTO_BROADCAST = 2;

    public function send($data, callable $onResponse = null, callable $onDelivery = null);

    public function invoke($call, $params = []): Promise;

    public function getId(): int;

    public function getHash(): string;

    public function getConnector(): ConnectorInterface;

    public function getListener(): ListenerInterface;

    public function isConnected(): bool;
}