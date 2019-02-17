<?php

namespace Proto\Invoke;

use Throwable;

class InvokeException extends \Exception
{
    const ERR_INVALID_INVOKE = 100;
    const ERR_INVALID_PARAMS = 101;
    const ERR_CLASS_NOT_FOUND = 110;
    const ERR_METHOD_NOT_FOUND = 111;
    const ERR_OPERATION_NOT_PERMITTED = 120;
    const ERR_UNKNOWN = 130;

    const MSG = [
        100 => 'Invalid invoke!',
        101 => 'Invalid params!',
        110 => 'Class not found!',
        111 => 'Method not found!',
        120 => 'Operation not permitted!',
        130 => 'Unknown error!',
    ];

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message != '' ? $message : self::MSG[$code], $code, $previous);
    }
}