<?php

namespace Proto\Broadcast;

use Proto\Pack\Pack;
use Proto\Pack\PackInterface;
use Proto\Socket\ProtoConnection;
use Proto\Socket\ProtoConnectionInterface;

class Broadcast implements BroadcastInterface
{
    private $listeners;

    public function emit($name, $data): BroadcastInterface
    {
        if (!isset($this->listeners[$name]))
            return $this;

        /**
         * @var $conn ProtoConnection
         */
        foreach ($this->listeners[$name] as $conn) {
            if (!$conn->isConnected())
                continue;

            $conn->broadcast(
                (new Pack())
                    ->setHeaderByKey(2, self::ACTION_EMITTED)
                    ->setHeaderByKey(3, $name)
                    ->setData($data)
            );
        }

        return $this;
    }

    public function income(PackInterface $pack, ProtoConnectionInterface $conn)
    {
        $action = $pack->getHeaderByKey(2);
        $name = $pack->getHeaderByKey(3);

        $identifyId = $conn->getId() === null ? $conn->getHash() : $conn->getId();

        switch ($action) {
            case self::ACTION_REGISTER:
                if (isset($this->listeners[$name][$identifyId]))
                    return;

                $this->listeners[$name][$identifyId] = $conn;
                break;

            case self::ACTION_UNREGISTER:
                if (isset($this->listeners[$name][$identifyId]))
                    unset($this->listeners[$name][$identifyId]);
                break;

            default:
                // TODO Error
        }
    }
}