<?php

namespace Proto\Invoke;

use Proto\OptConnectionInterface;
use Proto\Pack\PackInterface;

interface InvokeParserInterface
{
    /**
     * InvokeParserInterface constructor.
     * @param PackInterface $pack
     * @param OptConnectionInterface $opt
     * @throws InvokeException
     */
    public function __construct(PackInterface $pack, OptConnectionInterface $opt);

    public function getClass(): string;

    public function getMethod(): string;

    public function getParams(): array;
}