<?php

namespace Didi\Cloud\ItsMap;

use Illuminate\Database\Capsule\Manager as Capsule;


class MapManager
{
    public static $capsule;

    public static function bootEloquent($dbConnection = null)
    {
        if (empty($dbConnection)) {
            $dbConnection = \Didi\Cloud\ItsMap\Configs\Database::get("itsmap");
        }
        if (empty(self::$capsule)) {
            $capsule = new Capsule;
            $capsule->addConnection($dbConnection);
            $capsule->bootEloquent();
            $capsule->setAsGlobal();
            $capsule->getConnection()->enableQueryLog();
            self::$capsule = $capsule;
        }
    }

    public static function queryLog()
    {
        $connection = self::$capsule->getConnection();
        $logs = $connection->getQueryLog();
    }
}