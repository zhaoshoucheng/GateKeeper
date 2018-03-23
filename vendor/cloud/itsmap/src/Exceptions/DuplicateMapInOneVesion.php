<?php

namespace Didi\Cloud\ItsMap\Exceptions;

use Exception;

class DuplicateMapInOneVesion extends Exception
{
    public function __construct($logicJunctionId, $version)
    {
        parent::__construct();

        $this->message = "路口 {$logicJunctionId} 在版本 {$version} 存在不止一条记录";
    }

    protected $code = 10010;

    //protected $message = "同一个路口同一个版本存在两条记录";
}