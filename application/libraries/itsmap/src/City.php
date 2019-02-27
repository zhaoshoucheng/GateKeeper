<?php

namespace Didi\Cloud\ItsMap;

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Contracts\CityInterface;
use Didi\Cloud\ItsMap\Models\City as CityModel;

class City implements CityInterface
{
    public function __construct()
    {
        Env::init();
        MapManager::bootEloquent(Configs\Database::get("its"));
    }

    public function all()
    {
        $junctions = CityModel::all()->toArray();
        return $junctions;
    }
}