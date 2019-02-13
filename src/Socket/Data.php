<?php

namespace Proto\Socket;

use Proto\Pack\Pack;
use Proto\Pack\PackInterface;
use Proto\PromiseTransfer\ParserInterface;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\ProtoException;

class Data implements DataInterface
{
    /**
     * @var PackInterface
     */
    private $pack;

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var PromiseTransferInterface
     */
    private $transfer;

    private $isResponseSent = false;

    public function __construct(PackInterface $pack, ParserInterface $parser, PromiseTransferInterface $transfer)
    {
        $this->pack = $pack;
        $this->parser = $parser;
        $this->transfer = $transfer;
    }

    public function getPack(): PackInterface
    {
        return $this->pack;
    }

    public function getData()
    {
        return $this->pack->getData();
    }

    public function isWaitForResponse(): bool
    {
        return $this->parser->isWaitForResponse();
    }

    public function response($data, callable $onDelivery = null): bool
    {
        if ($this->isResponseSent)
            return false;

        // Security (Remove traces from exceptions)
        if ($data instanceof \Throwable)
            $data = new ProtoException(get_class($data), $data->getMessage(), $data->getCode());

        if (!$data instanceof PackInterface)
            $data = (new Pack())->setData($data);

        $this->transfer->response($data, $this->parser->getId(), $onDelivery);
        $this->isResponseSent = true;
        return true;
    }

    public function isResponseSent(): bool
    {
        return $this->isResponseSent;
    }
}