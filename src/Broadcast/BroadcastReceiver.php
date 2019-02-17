<?php

namespace Proto\Broadcast;

use Proto\Pack\Pack;
use Proto\Pack\PackInterface;
use Proto\Socket\Connection;
use Proto\Socket\ConnectionInterface;

class BroadcastReceiver implements BroadcastReceiverInterface
{
    /**
     * @var Connection
     */
    private $conn;

    private $listener = [];

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;
    }

    public function on($name, callable $callback): BroadcastReceiverInterface
    {
        $pack = (new Pack())
            ->setHeaderByKey(2, self::ACTION_REGISTER)
            ->setHeaderByKey(3, $name);

        $this->conn->broadcast($pack, null, function () use ($name, $callback) {
            $this->listener[$name][] = $callback;
        });

        return $this;
    }

    public function off($name): BroadcastReceiverInterface
    {
        $pack = (new Pack())
            ->setHeaderByKey(2, self::ACTION_UNREGISTER)
            ->setHeaderByKey(3, $name);

        $this->conn->broadcast($pack, null, function () use ($name) {
            unset($this->listener[$name]);
        });

        return $this;
    }

    public function income(PackInterface $pack)
    {
        if ($pack->getHeaderByKey(2) !== self::ACTION_EMITTED) {
            // TODO Error
            return;
        }

        $name = $pack->getHeaderByKey(3);
        $data = $pack->getData();

        if (!isset($this->listener[$name]) || empty($this->listener[$name]))
            return;

        foreach ($this->listener[$name] as $callable)
            $callable($data);

    }

}