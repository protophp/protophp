<?php

namespace Proto;

use Evenement\EventEmitter;
use Proto\Invoke\InvokeException;
use Proto\Invoke\InvokeParser;
use Proto\Pack\Pack;
use Proto\Pack\PackInterface;
use Proto\PromiseTransfer\ParserInterface;
use Proto\PromiseTransfer\PromiseTransferInterface;
use Proto\Session\SessionInterface;
use React\Promise\Deferred;
use React\Promise\Promise;

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

    /**
     * @var OptConnectionInterface
     */
    private $opt;

    public function send($data, callable $onResponse = null, callable $onDelivery = null)
    {
        if (!$data instanceof PackInterface)
            $data = (new Pack())->setData($data);

        $data->setHeaderByKey(1, self::PROTO_DATA);
        $this->transfer->send($data, $onResponse, $onDelivery);
    }

    public function invoke($call, $params = [], Deferred $deferred = null): Promise
    {
        if ($deferred === null)
            $deferred = new Deferred();

        $pack = (new Pack())->setHeaderByKey(1, self::PROTO_RPC)->setData([$call, $params]);
        $this->transfer->send($pack, function (PackInterface $pack) use ($deferred) {
            $return = $pack->getData();

            if ($return instanceof \Throwable)
                $deferred->reject($return);
            else
                $deferred->resolve($return);
        });

        return $deferred->promise();
    }

    public function setup(PromiseTransferInterface $transfer, SessionInterface $session, OptConnectionInterface $opt): ProtoConnectionInterface
    {
        $this->transfer = $transfer;
        $this->session = $session;
        $this->opt = $opt;

        $this->transfer->on('data', function (PackInterface $pack, ParserInterface $parser) {
            $data = new Data($pack, $parser, $this->transfer);

            switch ($pack->getHeaderByKey(1)) {
                case self::PROTO_DATA:
                    $this->emit('data', [$data]);
                    return;

                case self::PROTO_RPC:

                    try {
                        $parser = new InvokeParser($pack, $this->opt);
                    } catch (InvokeException $e) {
                        // Exception response
                        $data->response($e);
                        return;
                    }

                    $call = "{$parser->getClass()}::{$parser->getMethod()}";
                    $params = $parser->getParams();

                    // Call
                    try {
                        $result = $call(...$params);
                    } catch (\Throwable $e) {
                        // Exception response
                        $data->response($e);
                        return;
                    }

                    // On promise result
                    if ($result instanceof Promise) {

                        $result->then(function ($result) use ($data) {

                            $data->response($result);

                        })->otherwise(function ($e) use ($data) {

                            if ($e instanceof \Throwable)
                                $data->response($e);
                            else
                                $data->response(new \Exception("Unknown Error!"));

                        });

                    } else {
                        $data->response($result);
                    }
                    return;

                default:
                    // TODO

            }
        });

        return $this;
    }
}