<?php

namespace Proto;

use Proto\Session\SessionManagerInterface;
use Proto\Socket\Connector;
use Proto\Socket\Listener;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

class Proto implements ProtoInterface
{
    /**
     * @var SessionManagerInterface
     */
    private static $sessionManager;

    /**
     * @var LoopInterface
     */
    private static $loop;

    private static $connectors = [];
    private static $listeners = [];

    private $uri = null;
    private $name = null;
    private $logger = null;
    private $sessionKey = null;
    private $overwriteSessionManager = null;

    public function __construct()
    {
        if (!isset(self::$loop))
            throw new \Exception("ProtoPHP doesn't set up yet!");

        $this->overwriteSessionManager = self::$sessionManager;
    }

    public function uri(string $uri): ProtoInterface
    {
        $this->uri = $uri;
        return $this;
    }

    public function name($name): ProtoInterface
    {
        $this->name = $name;
        return $this;
    }

    public function logger(LoggerInterface $logger): ProtoInterface
    {
        $this->logger = $logger;
        return $this;
    }

    public function sessionKey(string $sessionKey): ProtoInterface
    {
        $this->sessionKey = $sessionKey;
        return $this;
    }

    public function sessionManager(SessionManagerInterface $sessionManager): ProtoInterface
    {
        $this->overwriteSessionManager = $sessionManager;
        return $this;
    }

    public function connect(): Connector
    {
        if (!isset($this->uri))
            throw new \Exception("The uri is not set!");

        $connector =
            (new Connector($this->uri, $this->overwriteSessionManager, $this->sessionKey))
                ->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, true)
                ->setOpt(ProtoOpt::MAP_INVOKE, [])
                ->connect();

        if (isset($this->name))
            self::$connectors[$this->name] = $connector;

        return $connector;
    }

    public function listen(): Listener
    {
        if (!isset($this->uri))
            throw new \Exception("The uri is not set!");

        $listener =
            (new Listener($this->uri, $this->overwriteSessionManager))
                ->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, true)
                ->setOpt(ProtoOpt::MAP_INVOKE, []);

        if (isset($this->name))
            self::$listeners[$this->name] = $listener;

        return $listener;
    }

    public static function getConnector($name): Connector
    {
        if (!isset(self::$connectors[$name]))
            throw new \Exception("Unable to found '$name' connector!");

        return self::$connectors[$name];
    }

    public static function getListener($name): Listener
    {
        if (!isset(self::$listeners[$name]))
            throw new \Exception("Unable to found '$name' listener!");

        return self::$listeners[$name];
    }

    public static function getLoop(): LoopInterface
    {
        if (!isset(self::$loop))
            throw new \Exception("ProtoPHP doesn't set up yet!");

        return self::$loop;
    }

    public static function getSessionManager(): SessionManagerInterface
    {
        if (!isset(self::$sessionManager))
            throw new \Exception("ProtoPHP doesn't set up yet!");

        return self::$sessionManager;
    }

    public static function setup(SessionManagerInterface $sessionManager, LoopInterface $loop)
    {
        self::$sessionManager = $sessionManager;
        self::$loop = $loop;

        // Include Annotations
        require_once __DIR__ . '/Annotations/RPC.php';
    }
}