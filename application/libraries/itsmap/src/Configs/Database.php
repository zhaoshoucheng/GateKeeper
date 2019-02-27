<?php

namespace Didi\Cloud\ItsMap\Configs;

class Database
{
    private static $configs = [
        ENV::DEVELOPMENT => [
            'itsmap' => [
                'driver' => 'mysql',
                'host' => '10.94.105.126',
                'port' => "3306",
                'database' => 'its_trafficflow',
                'username' => 'root',
                'password' => '123456',
            ],
            'its' => [
                'driver' => 'mysql',
                'host' => '10.94.105.126',
                'port' => "3306",
                'database' => 'its',
                'username' => 'root',
                'password' => '123456',
            ],
            'traffic_timing_solve' => [
                'driver' => 'mysql',
                'host' => '10.94.105.126',
                'port' => "3306",
                'database' => 'traffic_timing_solve',
                'username' => 'root',
                'password' => '123456',
            ],
        ],
        ENV::DEBUG => [
            'itsmap' => [
                'driver' => 'mysql',
                'host' => '100.90.164.31',
                'port' => "3306",
                'database' => 'its_trafficflow',
                'username' => 'root',
                'password' => 'Znjty@Didi@2017',
            ],
            'its' => [
                'driver' => 'mysql',
                'host' => '100.90.164.31',
                'port' => "3306",
                'database' => 'its',
                'username' => 'root',
                'password' => 'Znjty@Didi@2017',
            ],
            'traffic_timing_solve' => [
                'driver' => 'mysql',
                'host' => '100.90.164.31',
                'port' => "3306",
                'database' => 'traffic_timing_solve',
                'username' => 'root',
                'password' => 'Znjty@Didi@2017',
            ],
        ],
        ENV::ONLINE => [
            'itsmap' => [
                'driver' => 'mysql',
                'host' => '100.69.238.94',
                'port' => "4002",
                'database' => 'its_mapdata',
                'username' => 'its_mapdata_its_mapdata_r',
                'password' => 'txCjmkfIvMX2Fzn',
            ],
            'its' => [
                'driver' => 'mysql',
                'host' => '100.69.238.94',
                'port' => "4008",
                'database' => 'its',
                'username' => 'its_rw',
                'password' => 'iTsITs_Rw@0912',
            ],
            'traffic_timing_solve' => [
                'driver' => 'mysql',
                'host' => '100.69.238.94',
                'port' => "4008",
                'database' => 'traffic_timing_solve',
                'username' => 'traffic_timing_solve_rw',
                'password' => 'trAFfIcTs_rW@0912',
            ],
        ],
    ];

    /*
     * 获取某个db配置
     */
    public static function get($connection)
    {
        return self::$configs[ENV::$current][$connection];
    }
}