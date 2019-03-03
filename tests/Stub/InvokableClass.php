<?php

namespace Proto\Tests\Stub;

use Proto\Proto;
use Proto\RPC;
use React\Promise\Deferred;
use React\Promise\Promise;

/**
 * @RPC
 */
class InvokableClass
{
    /**
     * @RPC
     *
     * @param int $number1
     * @param int $number2
     * @return float|int
     */
    public function multiplication(int $number1, int $number2)
    {
        return $number1 * $number2;
    }

    /**
     * @RPC
     * @throws \Exception
     */
    public function exception()
    {
        throw new \Exception("Exception Message", 200);
    }

    public function promiseResolve(int $number1, int $number2): Promise
    {
        $deferred = new Deferred();

        Proto::getLoop()->futureTick(function () use ($number1, $number2, $deferred) {
            $deferred->resolve($number1 * $number2);
        });

        return $deferred->promise();
    }

    public function promiseReject(): Promise
    {
        $deferred = new Deferred();

        Proto::getLoop()->futureTick(function () use ($deferred) {
            $deferred->resolve(new \Exception("I'm a promise reject!"));
        });

        return $deferred->promise();
    }

}