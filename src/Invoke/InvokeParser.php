<?php

namespace Proto\Invoke;

use Doctrine\Common\Annotations\AnnotationReader;
use Proto\OptConnectionInterface;
use Proto\Pack\PackInterface;
use ReflectionClass;

class InvokeParser implements InvokeParserInterface
{
    private $class;
    private $method;
    private $params;

    public function __construct(PackInterface $pack, OptConnectionInterface $opt)
    {
        $invoke = $pack->getData();

        if (!is_array($invoke) || !isset($invoke[0]) || !isset($invoke[1]) || !is_array($invoke[1]))
            throw new InvokeException(null, InvokeException::ERR_INVALID_INVOKE);

        list($call, $this->params) = $invoke;

        // Parse call from map
        if (is_string($call) && strpos($call, '::') === false) {

            $map = (array)$opt->getOpt(OptConnectionInterface::OPT_MAP_INVOKE);
            if (!isset($map[$call]))
                throw new InvokeException(null, InvokeException::ERR_INVALID_INVOKE);

            $this->parse($map[$call]);
            return;
        }

        // Disallow direct invoke
        if ($opt->getOpt(OptConnectionInterface::OPT_DISALLOW_DIRECT_INVOKE))
            throw new InvokeException(null, InvokeException::ERR_OPERATION_NOT_PERMITTED);

        $this->parse($call);
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Parse
     * @param $call
     * @throws InvokeException
     */
    private function parse($call)
    {
        if (is_string($call))
            $this->parseStringCall($call);
        elseif (is_array($call))
            $this->parseArrayCall($call);
        else
            throw new InvokeException(null, InvokeException::ERR_INVALID_INVOKE);

        if (!class_exists($this->class))
            throw new InvokeException(null, InvokeException::ERR_CLASS_NOT_FOUND);

        if (!method_exists($this->class, $this->method))
            throw new InvokeException(null, InvokeException::ERR_METHOD_NOT_FOUND);

        try {
            $reflectionClass = new ReflectionClass($this->class);
            $classRPC = (new AnnotationReader())->getClassAnnotation($reflectionClass, 'RPC');
            if (!$classRPC)
                throw new InvokeException(null, InvokeException::ERR_OPERATION_NOT_PERMITTED);

            $reflectionMethod = $reflectionClass->getMethod($this->method);
            $methodRPC = (new AnnotationReader())->getMethodAnnotation($reflectionMethod, 'RPC');
            if (!$methodRPC)
                throw new InvokeException(null, InvokeException::ERR_OPERATION_NOT_PERMITTED);

        } catch (\Exception $e) {
            throw new InvokeException(null, InvokeException::ERR_UNKNOWN);
        }
    }

    /**
     * Parse string call
     * @param string $call
     * @throws InvokeException
     */
    private function parseStringCall(string $call)
    {
        if (strpos($call, '::') === false)
            throw new InvokeException(null, InvokeException::ERR_INVALID_INVOKE);

        list($this->class, $this->method) = explode('::', $call);
    }

    /**
     * Parse array call
     * @param array $call
     * @throws InvokeException
     */
    private function parseArrayCall(array $call)
    {
        if (!isset($call[0]) || !isset($call[1]))
            throw new InvokeException(null, InvokeException::ERR_INVALID_INVOKE);

        list($this->class, $this->method) = $call;
    }
}