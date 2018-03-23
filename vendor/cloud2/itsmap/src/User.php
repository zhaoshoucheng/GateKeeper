<?php

namespace Didi\Cloud\ItsMap;

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Contracts\NodeInterface;
use Didi\Cloud\ItsMap\Exceptions\MainNodeNotExistException;
use Didi\Cloud\ItsMap\Exceptions\NodeNotExistException;
use Didi\Cloud\ItsMap\Models\Version;
use Didi\Cloud\ItsMap\Services\RoadNet;
use Didi\Cloud\ItsMap\Supports\Arr;
use Didi\Cloud\ItsMap\Supports\Coordinate;
use Didi\Cloud\ItsMap\Models\Node as NodeModel;

class User
{
    private static $username = "guest";

    /*
     * 注册用户
     */
    public static function register($username)
    {
        if ($username) {
            self::$username = $username;
        }
    }

    public static function username()
    {
        return self::$username;
    }
}