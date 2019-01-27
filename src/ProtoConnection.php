<?php

namespace Proto;

use Evenement\EventEmitter;
use Proto\Pack\Pack;
use Proto\Pack\PackInterface;
use Proto\PromiseTransfer\ParserInterface;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\Session\SessionInterface;

class ProtoConnection extends EventEmitter implements ProtoConnectionInterface
{
    /**
     * @var PromiseTransferInterface
     */
    private $transfer;

    /**
     * @var SessionInterface
     */
    private $session;

    public function send($data, callable $onResponse = null, callable $onDelivery = null)
    {
        if (!$data instanceof PackInterface)
            $data = (new Pack())->setData($data);

        $this->transfer->send($data, $onResponse, $onDelivery);
    }

    public function setup(PromiseTransferInterface $transfer, SessionInterface $session): ProtoConnectionInterface
    {
        $this->transfer = $transfer;
        $this->session = $session;

        $this->transfer->on('data', function (PackInterface $pack, ParserInterface $parser) {
            $data = new Data($pack, $parser, $this->transfer);
            $this->emit('data', [$data]);
        });

        return $this;
    }
}