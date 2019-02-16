<?php

namespace Proto\Broadcast;

interface BroadcastInterface extends BroadcastConstants
{
    public function emit($name, $data): BroadcastInterface;
}