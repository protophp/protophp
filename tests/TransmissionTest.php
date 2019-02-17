<?php

namespace Proto\Tests;

use PHPUnit\Framework\TestCase;
use Proto\Proto;
use Proto\Socket\DataInterface;
use Proto\Pack\PackInterface;
use Proto\Socket\ConnectionInterface;
use Proto\Session\SessionManager;
use React\EventLoop\Factory;

class TransmissionTest extends TestCase
{
    public function test()
    {
        Proto::setup(new SessionManager(), Factory::create());

        // Find an unused unprivileged TCP port
        $port = (int)shell_exec('netstat -atn | awk \' /tcp/ {printf("%s\n",substr($4,index($4,":")+1,length($4) )) }\' | sed -e "s/://g" | sort -rnu | awk \'{array [$1] = $1} END {i=32768; again=1; while (again == 1) {if (array[i] == i) {i=i+1} else {print i; again=0}}}\'');

        (new Proto())
            ->uri("127.0.0.1:$port")
            ->listen()
            ->on('connection', function (ConnectionInterface $conn) {

                $this->assertTrue($conn instanceof ConnectionInterface);

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

        (new Proto())
            ->uri("127.0.0.1:$port")
            ->connect()
            ->on('connection', function (ConnectionInterface $conn) {

                $this->assertTrue($conn instanceof ConnectionInterface);

                $conn->send('MESSAGE',
                    function (PackInterface $pack) {
                        $this->assertTrue($pack instanceof PackInterface);

                    },
                    function () {
                        $this->assertTrue(true);
                    }
                );
            });

        Proto::getLoop()->run();
    }

    public function testQueue()
    {
        Proto::setup(new SessionManager(), Factory::create());

        // Find an unused unprivileged TCP port
        $port = (int)shell_exec('netstat -atn | awk \' /tcp/ {printf("%s\n",substr($4,index($4,":")+1,length($4) )) }\' | sed -e "s/://g" | sort -rnu | awk \'{array [$1] = $1} END {i=32768; again=1; while (again == 1) {if (array[i] == i) {i=i+1} else {print i; again=0}}}\'');

        (new Proto())
            ->uri("127.0.0.1:$port")
            ->listen()
            ->on('connection', function (ConnectionInterface $conn) {

                $this->assertTrue($conn instanceof ConnectionInterface);

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

        (new Proto())
            ->uri("127.0.0.1:$port")
            ->connect()
            ->on('connection', function (ConnectionInterface $conn) {
                $this->assertTrue($conn instanceof ConnectionInterface);
            })
            ->send('MESSAGE',
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