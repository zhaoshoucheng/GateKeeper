<?php

namespace Didi\Cloud\ItsMap;

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Models\Maptypeversion;

class MapVersion {
    public function __construct()
    {
        Env::init();
        MapManager::bootEloquent(Configs\Database::get("traffic_timing_solve"));
    }

    public static function getDateVersion($date)
    {
        $mapVersion = new Maptypeversion();
        $all = $mapVersion->get($date)->toArray();
        return $all;
    }
}