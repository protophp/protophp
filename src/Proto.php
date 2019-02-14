<?php

namespace Proto;

use Proto\Session\SessionManagerInterface;
use Proto\Socket\Connector;
use Proto\Socket\Listener;
use React\EventLoop\LoopInterface;

class Proto implements ProtoInterface
{
    /**
     * @var ProtoInterface
     */
    private static $instance;

    /**
     * @var SessionManagerInterface
     */
    private static $sessionManager;

    /**
     * @var LoopInterface
     */
    private static $loop;

    private function __construct()
    {
    }

    public function connector(string $uri, string $sessionKey = null, SessionManagerInterface $sessionManager = null): Connector
    {
        return
            (new Connector($uri, self::$loop, ($sessionManager === null) ? self::$sessionManager : $sessionManager, $sessionKey))
                ->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, true)
                ->setOpt(ProtoOpt::MAP_INVOKE, []);
    }

    public function listener($uri, SessionManagerInterface $sessionManager = null): Listener
    {
        return
            (new Listener($uri, self::$loop, ($sessionManager === null) ? self::$sessionManager : $sessionManager))
                ->setOpt(ProtoOpt::DISALLOW_DIRECT_INVOKE, true)
                ->setOpt(ProtoOpt::MAP_INVOKE, []);
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

    public static function getInstance(): ProtoInterface
    {
        if (isset(self::$instance))
            return self::$instance;

        if (!isset(self::$loop))
            throw new \Exception("ProtoPHP doesn't set up yet!");

        return self::$instance = new self();
    }

    public static function setup(SessionManagerInterface $sessionManager, LoopInterface $loop): ProtoInterface
    {
        self::$sessionManager = $sessionManager;
        self::$loop = $loop;

        // Include Annotations
        require_once __DIR__ . '/Annotations/RPC.php';

        return self::getInstance();
    }
}