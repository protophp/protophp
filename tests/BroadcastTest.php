<?php

namespace Proto\Tests;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Proto\Proto;
use Proto\Session\SessionManager;
use Proto\Socket\ConnectionInterface;
use React\EventLoop\Factory;

class BroadcastTest extends TestCase
{
    public function testBroadcastToOneConnector()
    {
        Proto::setup(new SessionManager(), Factory::create());

        // Find an unused unprivileged TCP port
        $port = (int)shell_exec('netstat -atn | awk \' /tcp/ {printf("%s\n",substr($4,index($4,":")+1,length($4) )) }\' | sed -e "s/://g" | sort -rnu | awk \'{array [$1] = $1} END {i=32768; again=1; while (again == 1) {if (array[i] == i) {i=i+1} else {print i; again=0}}}\'');

        $listener =
            (new Proto())
                ->uri("127.0.0.1:$port")
                ->logger(new Logger('TestListener', [new ErrorLogHandler()]))
                ->listen();

        (new Proto())
            ->uri("127.0.0.1:$port")
            ->logger(new Logger('TestConnector', [new ErrorLogHandler()]))
            ->connect()
            ->on('connection', function (ConnectionInterface $conn) {
                $conn->getConnector()->broadcast()->on('SampleBroadcast', function ($data) {
                    $this->assertSame('BroadcastDATA', $data);
                    Proto::getLoop()->stop();
                });
            });

        Proto::getLoop()->addTimer(.01, function () use ($listener) {
            $listener->broadcast()->emit('SampleBroadcast', 'BroadcastDATA');
        });

        Proto::getLoop()->run();
    }

    public function testBroadcastToMultiConnector()
    {
        Proto::setup(new SessionManager(), Factory::create());

        // Find an unused unprivileged TCP port
        $port = (int)shell_exec('netstat -atn | awk \' /tcp/ {printf("%s\n",substr($4,index($4,":")+1,length($4) )) }\' | sed -e "s/://g" | sort -rnu | awk \'{array [$1] = $1} END {i=32768; again=1; while (again == 1) {if (array[i] == i) {i=i+1} else {print i; again=0}}}\'');

        $listener =
            (new Proto())
                ->uri("127.0.0.1:$port")
                ->listen();

        (new Proto())
            ->uri("127.0.0.1:$port")
            ->connect()
            ->on('connection', function (ConnectionInterface $conn) {
                $conn->getConnector()->broadcast()->on('SampleBroadcast', function ($data) {
                    $this->assertSame('BroadcastDATA', $data);
                });
            });

        (new Proto())
            ->uri("127.0.0.1:$port")
            ->connect()
            ->on('connection', function (ConnectionInterface $conn) {
                $conn->getConnector()->broadcast()->on('SampleBroadcast', function ($data) {
                    $this->assertSame('BroadcastDATA', $data);
                });
            });

        Proto::getLoop()->addTimer(.01, function () use ($listener) {
            $listener->broadcast()->emit('SampleBroadcast', 'BroadcastDATA');
            Proto::getLoop()->addTimer(.05, function () {
                $this->assertEquals(2, $this->getCount());
                Proto::getLoop()->stop();
            });
        });

        Proto::getLoop()->run();
    }
}