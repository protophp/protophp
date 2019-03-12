<?php

namespace Proto;

use Opt\OptInterface;

interface ProtoOpt extends OptInterface
{
    const DISALLOW_DIRECT_INVOKE = 10;
    const MAP_INVOKE = 20;
    const RETRY_CONNECTION = 30;
}