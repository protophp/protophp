<?php

namespace Proto;

use Proto\Pack\PackInterface;
use Proto\PromiseTransfer\ParserInterface;
use Proto\PromiseTransfer\PromiseTransferInterface;

interface DataInterface
{
    public function __construct(PackInterface $pack, ParserInterface $parser, PromiseTransferInterface $transfer);

    public function getPack(): PackInterface;

    public function getData();

    public function isWaitForResponse(): bool;

    public function isResponseSent(): bool;

    public function response($data, callable $onDelivery = null): bool;
}