<?php

namespace Proto;

class ProtoException
{
    private $name;
    private $message;
    private $code;

    public function __construct(string $name = "", string $message = "", int $code = 0)
    {
        $this->name = $name;
        $this->message = $message;
        $this->code = $code;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function __toString()
    {
        return "[$this->name] [$this->code] $this->message";
    }
}