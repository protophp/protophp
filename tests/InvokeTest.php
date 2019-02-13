<?php

namespace Proto\Tests;

use PHPUnit\Framework\TestCase;
use Proto\Proto;
use Proto\ProtoException;
use Proto\ProtoOpt;
use Proto\Session\SessionManager;
use React\EventLoop\Factory;

class InvokeTest extends TestCase
{
    public function testInvoke()
    {
        $proto = Proto::setup(new SessionManager(), Factory::create());

        // Find an unused unprivileged TCP port
        $port = (int)shell_exec('netstat -atn | awk \' /tcp/ {printf("%s\n",substr($4,index($4,":")+1,length($4) )) }\' | sed -e "s/://g" | sort -rnu | awk \'{array [$1] = $1} END {i=32768; again=1; while (again == 1) {if (array[i] == i) {i=i+1} else {print i; again=0}}}\'');

        $proto->listener("127.0.0.1:$port")
            ->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, false);

        $connector = $proto->connector("127.0.0.1:$port")->connect();

        $connector->invoke('\Proto\Tests\Stub\InvokableClass::multiplication', [5, 10])
            ->then(function ($data) {
                $this->assertEquals(50, $data);
                Proto::getLoop()->stop();
            })
            ->otherwise(function (ProtoException $e) {
                var_dump($e);
                die;
            });

        Proto::getLoop()->run();
    }
}