<?php

namespace Proto\Invoke;

use Proto\Pack\PackInterface;

interface InvokeParserInterface
{
    public function __construct(PackInterface $pack);

    public function getClass(): string;

    public function getMethod(): string;

    public function getParams(): array;
}