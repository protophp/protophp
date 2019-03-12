<?php

namespace Proto\Tests;

use PHPUnit\Framework\TestCase;
use Proto\Proto;
use Proto\ProtoOpt;
use Proto\Session\SessionManager;
use React\EventLoop\Factory;

class InvokeTest extends TestCase
{
    public function testInvoke()
    {
        Proto::setup(new SessionManager(), Factory::create());

        // Find an unused unprivileged TCP port
        $port = (int)shell_exec('netstat -atn | awk \' /tcp/ {printf("%s\n",substr($4,index($4,":")+1,length($4) )) }\' | sed -e "s/://g" | sort -rnu | awk \'{array [$1] = $1} END {i=32768; again=1; while (again == 1) {if (array[i] == i) {i=i+1} else {print i; again=0}}}\'');

        (new Proto())
            ->uri("127.0.0.1:$port")
            ->listen()
            ->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, false);

        (new Proto())
            ->uri("127.0.0.1:$port")
            ->connect()
            ->invoke('\Proto\Tests\Stub\InvokableClass::multiplication', [5, 10])
            ->then(function ($data) {
                $this->assertEquals(50, $data);
                Proto::getLoop()->stop();
            })
            ->otherwise(function (\Throwable $e) {
                var_dump($e);
                die;
            });

        Proto::getLoop()->run();
    }

    public function testInvokeException()
    {
        Proto::setup(new SessionManager(), Factory::create());

        // Find an unused unprivileged TCP port
        $port = (int)shell_exec('netstat -atn | awk \' /tcp/ {printf("%s\n",substr($4,index($4,":")+1,length($4) )) }\' | sed -e "s/://g" | sort -rnu | awk \'{array [$1] = $1} END {i=32768; again=1; while (again == 1) {if (array[i] == i) {i=i+1} else {print i; again=0}}}\'');

        (new Proto())
            ->uri("127.0.0.1:$port")
            ->listen()
            ->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, false);

        (new Proto())
            ->uri("127.0.0.1:$port")
            ->connect()
            ->invoke('\Proto\Tests\Stub\InvokableClass::exception')
            ->otherwise(function (\Throwable $e) {
                $this->assertSame('Exception Message', $e->getMessage());
                $this->assertEquals(200, $e->getCode());
                Proto::getLoop()->stop();
            });

        Proto::getLoop()->run();
    }
}