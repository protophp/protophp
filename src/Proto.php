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

    public $uri = null;
    public $name = null;
    public $logger = null;
    public $sessionKey = null;
    public $overwriteSessionManager = null;

    public function __construct()
    {
        if (!isset(self::$loop))
            return null;

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
        try {
            $connector =
                (new Connector($this))
                    ->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, true)
                    ->setOpt(ProtoOpt::MAP_INVOKE, [])
                    ->connect();
        } catch (Session\Exception\SessionException $e) {
            // TODO: Log Error
            return null;
        }

        if (isset($this->name))
            self::$connectors[$this->name] = $connector;

        return $connector;
    }

    public function listen(): Listener
    {
        $listener =
            (new Listener($this))
                ->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, true)
                ->setOpt(ProtoOpt::MAP_INVOKE, []);

        if (isset($this->name))
            self::$listeners[$this->name] = $listener;

        return $listener;
    }

    public static function getConnector($name): Connector
    {
        if (!isset(self::$connectors[$name]))
            return null;

        return self::$connectors[$name];
    }

    public static function getListener($name): Listener
    {
        if (!isset(self::$listeners[$name]))
            return null;

        return self::$listeners[$name];
    }

    public static function getLoop(): LoopInterface
    {
        if (!isset(self::$loop))
            return null;

        return self::$loop;
    }

    public static function getSessionManager(): SessionManagerInterface
    {
        if (!isset(self::$sessionManager))
            return null;

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