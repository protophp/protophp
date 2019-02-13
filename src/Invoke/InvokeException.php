<?php

namespace Proto\Invoke;

class InvokeException extends \Exception
{
    const ERR_INVALID_INVOKE = 100;
    const ERR_INVALID_PARAMS = 101;
    const ERR_CLASS_NOT_FOUND = 110;
    const ERR_METHOD_NOT_FOUND = 111;
    const ERR_OPERATION_NOT_PERMITTED = 120;
    const ERR_UNKNOWN = 130;
}