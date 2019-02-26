<?php
namespace Didi\Cloud\TraceLog;


class TraceLogFactory
{
    private static $loggerMap;

    private function __construct(){}

    private function __clone(){}

    /**
     * 获取TraceLog实例
     * @param $name
     * @param string $path
     * @param array $actionMap
     * @param int $userGlobalTraceId
     * @return TraceLog
     */
    public static function getInstance($name, $path="", $actionMap=[], $platform="itstool", $userGlobalTraceId=0)
    {
        if (!isset(self::$loggerMap[$name])) {
            self::$loggerMap[$name] = new TraceLog($name, $path, $actionMap, $platform, $userGlobalTraceId);
        }
        return self::$loggerMap[$name];
    }
}