<?php

namespace Proto\Socket;

use Evenement\EventEmitterInterface;
use React\Promise\Deferred;
use React\Promise\Promise;

interface ProtoConnectionInterface extends EventEmitterInterface
{
    const PROTO_DATA = 0;
    const PROTO_RPC = 1;

    public function send($data, callable $onResponse = null, callable $onDelivery = null);

    public function invoke($call, $params = [], Deferred $deferred = null): Promise;
}