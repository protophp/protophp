<?php

namespace Proto\Tests;

use PHPUnit\Framework\TestCase;
use Proto\Proto;
use Proto\Socket\DataInterface;
use Proto\Pack\PackInterface;
use Proto\Socket\ProtoConnectionInterface;
use Proto\Session\SessionManager;
use React\EventLoop\Factory;

class TransmissionTest extends TestCase
{
    public function test()
    {
        Proto::setup(new SessionManager(), Factory::create());
        $proto = Proto::getInstance();

        // Find an unused unprivileged TCP port
        $port = (int)shell_exec('netstat -atn | awk \' /tcp/ {printf("%s\n",substr($4,index($4,":")+1,length($4) )) }\' | sed -e "s/://g" | sort -rnu | awk \'{array [$1] = $1} END {i=32768; again=1; while (again == 1) {if (array[i] == i) {i=i+1} else {print i; again=0}}}\'');

        $proto->listener("127.0.0.1:$port")
            ->on('connection', function (ProtoConnectionInterface $conn) {

                $this->assertTrue($conn instanceof ProtoConnectionInterface);

                $conn->on('data', function (DataInterface $data) {

                    $this->assertTrue($data instanceof DataInterface);
                    $this->assertSame('MESSAGE', $data->getData());

                    $data->response("RESPONSE-MESSAGE", function () {
                        $this->assertTrue(true);
                        $this->assertEquals(7, $this->getCount());
                        Proto::getLoop()->stop();
                    });
                });
            });

        $proto->connector("127.0.0.1:$port")
            ->on('connection', function (ProtoConnectionInterface $conn) {

                $this->assertTrue($conn instanceof ProtoConnectionInterface);

                $conn->send('MESSAGE',
                    function (PackInterface $pack) {
                        $this->assertTrue($pack instanceof PackInterface);

                    },
                    function () {
                        $this->assertTrue(true);
                    }
                );
            })
            ->connect();

        Proto::getLoop()->run();
    }

    public function testQueue()
    {
        Proto::setup(new SessionManager(), Factory::create());
        $proto = Proto::getInstance();

        // Find an unused unprivileged TCP port
        $port = (int)shell_exec('netstat -atn | awk \' /tcp/ {printf("%s\n",substr($4,index($4,":")+1,length($4) )) }\' | sed -e "s/://g" | sort -rnu | awk \'{array [$1] = $1} END {i=32768; again=1; while (again == 1) {if (array[i] == i) {i=i+1} else {print i; again=0}}}\'');

        $proto->listener("127.0.0.1:$port")
            ->on('connection', function (ProtoConnectionInterface $conn) {

                $this->assertTrue($conn instanceof ProtoConnectionInterface);

                $conn->on('data', function (DataInterface $data) {

                    $this->assertTrue($data instanceof DataInterface);
                    $this->assertSame('MESSAGE', $data->getData());

                    $data->response("RESPONSE-MESSAGE", function () {
                        $this->assertTrue(true);
                        $this->assertEquals(7, $this->getCount());
                        Proto::getLoop()->stop();
                    });
                });
            });

        $connector = $proto->connector("127.0.0.1:$port");
        $connector->on('connection', function (ProtoConnectionInterface $conn) {
            $this->assertTrue($conn instanceof ProtoConnectionInterface);
        })->connect();

        $connector->send('MESSAGE',
            function (PackInterface $pack) {
                $this->assertTrue($pack instanceof PackInterface);

            },
            function () {
                $this->assertTrue(true);
            }
        );

        Proto::getLoop()->run();
    }
}