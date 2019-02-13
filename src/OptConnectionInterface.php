<?php

namespace Proto;

use Opt\OptInterface;

interface OptConnectionInterface extends OptInterface
{
    const OPT_DISALLOW_DIRECT_INVOKE = 10;
    const OPT_MAP_INVOKE = 20;
}