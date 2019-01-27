<?php

namespace Proto;

use Evenement\EventEmitterInterface;

interface ProtoConnectionInterface extends EventEmitterInterface
{
    public function send($data, callable $onResponse = null, callable $onDelivery = null);
}