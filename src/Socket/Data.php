<?php

namespace Proto\Socket;

use Proto\Pack\Pack;
use Proto\Pack\PackInterface;
use Proto\PromiseTransfer\ParserInterface;
use Proto\PromiseTransfer\PromiseTransferInterface;

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

    /**
     * @var \Throwable
     */
    private $exception;
    private $isResponseSent = false;
    private $isException = false;

    public function __construct(PackInterface $pack, ParserInterface $parser, PromiseTransferInterface $transfer)
    {
        $this->pack = $pack;
        $this->parser = $parser;
        $this->transfer = $transfer;

        if ($pack->getHeaderByKey(PROTO_RESERVED_KEY) === ConnectionInterface::PROTO_EXCEPTION) {
            $this->isException = true;

            list($class, $message, $code) = $this->pack->getData();
            $this->exception = class_exists($class) ? new $class($message, $code) : new \Exception($message, $code);
        }
    }

    public function getPack(): PackInterface
    {
        return $this->pack;
    }

    public function getData()
    {
        return $this->isException ? null : $this->pack->getData();
    }

    public function getException(): \Throwable
    {
        return $this->exception;
    }

    public function isWaitForResponse(): bool
    {
        return $this->parser->isWaitForResponse();
    }

    public function isResponseSent(): bool
    {
        return $this->isResponseSent;
    }

    public function isException(): bool
    {
        return $this->isException;
    }

    public function response($data, callable $onDelivery = null): bool
    {
        if ($this->isResponseSent)
            return false;

        if (!$data instanceof PackInterface)
            $data = self::data2pack($data);

        $this->transfer->response($data, $this->parser->getId(), $onDelivery);
        $this->isResponseSent = true;
        return true;
    }

    public static function data2pack($data): PackInterface
    {
        if ($data instanceof \Throwable)
            return (new Pack())
                ->setHeaderByKey(PROTO_RESERVED_KEY, ConnectionInterface::PROTO_EXCEPTION)
                ->setData([get_class($data), $data->getMessage(), $data->getCode()]);
        else
            return (new Pack())
                ->setHeaderByKey(PROTO_RESERVED_KEY, ConnectionInterface::PROTO_DATA)
                ->setData($data);
    }
}