<?php

namespace Proto\Socket;

use Evenement\EventEmitterInterface;
use Proto\Broadcast\BroadcastInterface;
use Proto\Proto;
use Proto\ProtoOpt;

interface ListenerInterface extends EventEmitterInterface, ProtoOpt
{
    /**
     * ListenerInterface constructor.
     * @param Proto $proto
     */
    public function __construct(Proto $proto);

    public function broadcast(): BroadcastInterface;
}