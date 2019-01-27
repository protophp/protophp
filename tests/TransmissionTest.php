<?php

namespace Proto\Tests;

use PHPUnit\Framework\TestCase;
use Proto\Connector;
use Proto\DataInterface;
use Proto\Listener;
use Proto\Pack\PackInterface;
use Proto\ProtoConnectionInterface;
use Proto\Session\SessionManager;
use React\EventLoop\Factory;

class TransmissionTest extends TestCase
{
    public function test()
    {
        $loop = Factory::create();
        $sessionManager = new SessionManager();

        // Find an unused unprivileged TCP port
        $port = (int)shell_exec('netstat -atn | awk \' /tcp/ {printf("%s\n",substr($4,index($4,":")+1,length($4) )) }\' | sed -e "s/://g" | sort -rnu | awk \'{array [$1] = $1} END {i=32768; again=1; while (again == 1) {if (array[i] == i) {i=i+1} else {print i; again=0}}}\'');

        $listener = new Listener("127.0.0.1:$port", $loop, $sessionManager);
        $listener->on('connection', function (ProtoConnectionInterface $conn) use ($loop) {

            $this->assertTrue($conn instanceof ProtoConnectionInterface);

            $conn->on('data', function (DataInterface $data) use ($loop) {

                $this->assertTrue($data instanceof DataInterface);
                $this->assertSame('MESSAGE', $data->getData());

                $data->response("RESPONSE-MESSAGE", function () use ($loop) {
                    $this->assertTrue(true);
                    $this->assertEquals(7, $this->getCount());
                    $loop->stop();
                });
            });
        });

        $connector = new Connector("127.0.0.1:$port", $loop, $sessionManager);
        $connector->on('connection', function (ProtoConnectionInterface $conn) {

            $this->assertTrue($conn instanceof ProtoConnectionInterface);

            $conn->send('MESSAGE',
                function (PackInterface $pack) {
                    $this->assertTrue($pack instanceof PackInterface);

                },
                function () {
                    $this->assertTrue(true);
                }
            );
        });

        $connector->connect();

        $loop->run();
    }
}