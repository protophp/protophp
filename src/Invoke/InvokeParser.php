<?php

namespace Proto\Invoke;

use Doctrine\Common\Annotations\AnnotationReader;
use Proto\Pack\PackInterface;
use ReflectionClass;

class InvokeParser implements InvokeParserInterface
{
    private $class;
    private $method;
    private $params;

    public function __construct(PackInterface $pack)
    {
        $invoke = $pack->getData();

        if (!is_array($invoke) || !isset($invoke[0]) || !isset($invoke[1]))
            throw new InvokeException(null, InvokeException::ERR_INVALID_INVOKE);

        list($call, $params) = $invoke;

        if (is_string($call)) {

            // TODO

        } elseif (is_array($call)) {

            // TODO

        } else
            throw new InvokeException(null, InvokeException::ERR_INVALID_INVOKE);

        list($class, $method) = explode('::', $call);

        if (!class_exists($class))
            throw new InvokeException(null, InvokeException::ERR_CLASS_NOT_FOUND);

        if (!method_exists($class, $method))
            throw new InvokeException(null, InvokeException::ERR_METHOD_NOT_FOUND);

        try {

            $reflectionClass = new ReflectionClass($class);
            $classRPC = (new AnnotationReader())->getClassAnnotation($reflectionClass, 'RPC');
            if (!$classRPC)
                throw new InvokeException(null, InvokeException::ERR_OPERATION_NOT_PERMITTED);

            $reflectionMethod = $reflectionClass->getMethod($method);
            $methodRPC = (new AnnotationReader())->getMethodAnnotation($reflectionMethod, 'RPC');
            if (!$methodRPC)
                throw new InvokeException(null, InvokeException::ERR_OPERATION_NOT_PERMITTED);

        } catch (\Exception $e) {
            throw new InvokeException(null, InvokeException::ERR_UNKNOWN);
        }

        $this->class = $class;
        $this->method = $method;
        $this->params = $params;
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
}