<?php

namespace Proto\Invoke;

use Opt\OptInterface;
use Proto\Pack\PackInterface;

interface InvokeParserInterface
{
    /**
     * InvokeParserInterface constructor.
     * @param PackInterface $pack
     * @param OptInterface $opt
     * @throws InvokeException
     */
    public function __construct(PackInterface $pack, OptInterface $opt);

    public function getClass(): string;

    public function getMethod(): string;

    public function getParams(): array;
}