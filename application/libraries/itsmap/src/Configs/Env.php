<?php

namespace Didi\Cloud\ItsMap\Configs;

class Env
{
    const DEVELOPMENT = 'development';
    const DEBUG = 'debug';
    const TEST = 'test';
    const ONLINE = 'online';

    public static $current = Env::DEVELOPMENT; // development/ debug/ test / online

    /*
     * 判断当前的环境
     */
    public static function init()
    {
        $hosts = [
            'ipd-cloud-web00.gz01' => self::ONLINE,
            'ipd-cloud-web01.gz01' => self::ONLINE,
            'ipd-cloud-preweb00.gz01' => self::ONLINE,
            'localhost'                     => self::DEVELOPMENT,
            'ipd-cloud-server01.gz01'       => self::DEBUG,
        ];

        $hostname = gethostname();

        if (isset($hosts[$hostname])) {
            self::$current = $hosts[$hostname];
        }
    }
}