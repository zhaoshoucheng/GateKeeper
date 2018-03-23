<?php

namespace Didi\Cloud\ItsMap\Services;

/*
 * 导航路网提供的日志服务
 */
class Log
{
    private static $logger = null;

    public static function registerLogger($logger)
    {
        self::$logger = $logger;
    }

    public static function notice($message)
    {
        if (self::$logger instanceof  \CI_Log) {
            self::$logger->write_log("notice", $message);
        }
    }

    public static function error()
    {
        if (self::$logger instanceof  \CI_Log) {
            self::$logger->write_log("error", $message);
        }
    }

    public static function debug()
    {
        if (self::$logger instanceof  \CI_Log) {
            self::$logger->write_log("debug", $message);
        }
    }
}
