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

        foreach ($logs as $log) {
            com_log_strace('_com_mysql_success', array('host'=>$connection->getConfig('host'), 'port'=>$connection->getConfig('port'), "oper_type"=>"query", 'table'=>'', "sql"=> $log['query'], 'bindings' => json_encode($log['bindings']), 'proc_time' => $log['time'] / 1000));
        }

        $connection->flushQueryLog();
    }
}