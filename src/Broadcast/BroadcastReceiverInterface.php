<?php

namespace Proto\Broadcast;

interface BroadcastReceiverInterface extends BroadcastConstants
{
    public function on($name, callable $callback): BroadcastReceiverInterface;

    public function off($name): BroadcastReceiverInterface;
}