<?php

namespace Proto\Tests;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Opt\OptInterface;
use Opt\OptTrait;
use PHPUnit\Framework\TestCase;
use Proto\Invoke\InvokeException;
use Proto\Invoke\InvokeParser;
use Proto\ProtoOpt;
use Proto\Pack\Pack;

/**
 * @Proto\RPC
 */
class InvokeParserTest extends TestCase implements OptInterface
{
    use OptTrait;

    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // Defaults options
        $this
            ->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, true)
            ->setOpt(ProtoOpt::MAP_INVOKE, []);

        /**
         * @var ClassLoader $loader
         */
        $loader = require __DIR__ . '/../vendor/autoload.php';
        AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
    }

    /**
     * @Proto\RPC
     */
    public function method()
    {

    }

    public function methodWithoutAnnotation()
    {

    }

    /**
     * @param int $int
     * @param string $string
     * @param array $array
     *
     * @Proto\RPC
     */
    public function methodWithParams(int $int, string $string, array $array = [])
    {

    }

    public function testAllowDirectStringCall()
    {
        $call = '\Proto\Tests\InvokeParserTest::method';
        $params = [];
        $pack = (new Pack())->setData([$call, $params]);

        $this->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, false);

        try {
            $parser = new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            die('InvokeException: ' . $e->getCode());
        }

        $this->assertSame('\Proto\Tests\InvokeParserTest', $parser->getClass());
        $this->assertSame('method', $parser->getMethod());
        $this->assertSame($params, $parser->getParams());
    }

    public function testDisallowDirectStringCall()
    {
        $call = '\Proto\Tests\InvokeParserTest::method';
        $params = [];
        $pack = (new Pack())->setData([$call, $params]);

        try {
            new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            $this->assertEquals(InvokeException::ERR_OPERATION_NOT_PERMITTED, $e->getCode());
        }

        $this->assertEquals(1, $this->getCount());
    }

    public function testAllowDirectArrayCall()
    {
        $call = ['\Proto\Tests\InvokeParserTest', 'method'];
        $params = [];
        $pack = (new Pack())->setData([$call, $params]);

        $this->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, false);

        try {
            $parser = new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            die('InvokeException: ' . $e->getCode());
        }

        $this->assertSame('\Proto\Tests\InvokeParserTest', $parser->getClass());
        $this->assertSame('method', $parser->getMethod());
        $this->assertSame($params, $parser->getParams());
    }

    public function testDisallowDirectArrayCall()
    {
        $call = ['\Proto\Tests\InvokeParserTest', 'method'];
        $params = [];
        $pack = (new Pack())->setData([$call, $params]);

        try {
            new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            $this->assertEquals(InvokeException::ERR_OPERATION_NOT_PERMITTED, $e->getCode());
        }

        $this->assertEquals(1, $this->getCount());
    }

    public function testMapToStringCall()
    {
        $this->setOpt(ProtoOpt::MAP_INVOKE, [
            'TEST' => '\Proto\Tests\InvokeParserTest::method'
        ]);

        $call = 'TEST';
        $params = [];
        $pack = (new Pack())->setData([$call, $params]);

        $this->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, false);

        try {
            $parser = new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            die('InvokeException: ' . $e->getCode());
        }

        $this->assertSame('\Proto\Tests\InvokeParserTest', $parser->getClass());
        $this->assertSame('method', $parser->getMethod());
        $this->assertSame($params, $parser->getParams());
    }

    public function testMapToArrayCall()
    {
        $this->setOpt(ProtoOpt::MAP_INVOKE, [
            'TEST' => ['\Proto\Tests\InvokeParserTest', 'method']
        ]);

        $call = 'TEST';
        $params = [];
        $pack = (new Pack())->setData([$call, $params]);

        $this->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, false);

        try {
            $parser = new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            die('InvokeException: ' . $e->getCode());
        }

        $this->assertSame('\Proto\Tests\InvokeParserTest', $parser->getClass());
        $this->assertSame('method', $parser->getMethod());
        $this->assertSame($params, $parser->getParams());
    }

    public function testInvalidInvokes()
    {
        $this->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, false);

        $call = 1;
        $params = [];
        $pack = (new Pack())->setData([$call, $params]);

        try {
            new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            $this->assertEquals(InvokeException::ERR_INVALID_INVOKE, $e->getCode());
        }

        $call = 'Invalid Call';
        $params = [];
        $pack = (new Pack())->setData([$call, $params]);

        try {
            new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            $this->assertEquals(InvokeException::ERR_INVALID_INVOKE, $e->getCode());
        }

        $call = '\Proto\Tests\InvokeParserTest::InvalidMethod';
        $params = [];
        $pack = (new Pack())->setData([$call, $params]);

        try {
            new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            $this->assertEquals(InvokeException::ERR_METHOD_NOT_FOUND, $e->getCode());
        }

        $call = 'InvalidClass::method';
        $params = [];
        $pack = (new Pack())->setData([$call, $params]);

        try {
            new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            $this->assertEquals(InvokeException::ERR_CLASS_NOT_FOUND, $e->getCode());
        }

        $call = '\Proto\Tests\InvokeParserTest::methodWithoutAnnotation';
        $params = [];
        $pack = (new Pack())->setData([$call, $params]);

        try {
            new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            $this->assertEquals(InvokeException::ERR_OPERATION_NOT_PERMITTED, $e->getCode());
        }

        $this->assertEquals(5, $this->getCount());
    }

    public function testValidNumberOfRequiredParameters()
    {
        $this->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, false);

        // All parameters passed
        $call = '\Proto\Tests\InvokeParserTest::methodWithParams';
        $params = [1, 'A', ['Foo' => 'Bar']];
        $pack = (new Pack())->setData([$call, $params]);

        try {
            $parser = new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            die('InvokeException: ' . $e->getCode());
        }

        $this->assertSame('\Proto\Tests\InvokeParserTest', $parser->getClass());
        $this->assertSame('methodWithParams', $parser->getMethod());
        $this->assertSame($params, $parser->getParams());


        // Only required parameters passed
        $call = '\Proto\Tests\InvokeParserTest::methodWithParams';
        $params = [1, 'A'];
        $pack = (new Pack())->setData([$call, $params]);

        try {
            $parser = new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            die('InvokeException: ' . $e->getCode());
        }

        $this->assertSame('\Proto\Tests\InvokeParserTest', $parser->getClass());
        $this->assertSame('methodWithParams', $parser->getMethod());
        $this->assertSame($params, $parser->getParams());
    }

    public function testInvalidNumberOfRequiredParameters()
    {
        $this->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, false);

        $call = '\Proto\Tests\InvokeParserTest::methodWithParams';
        $params = [1];
        $pack = (new Pack())->setData([$call, $params]);

        try {
            new InvokeParser($pack, $this);
        } catch (InvokeException $e) {
            $this->assertEquals(InvokeException::ERR_INVALID_PARAMS, $e->getCode());
        }

        $this->assertEquals(1, $this->getCount());
    }


}