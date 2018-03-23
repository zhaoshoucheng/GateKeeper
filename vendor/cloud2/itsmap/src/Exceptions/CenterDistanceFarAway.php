<?php

namespace Didi\Cloud\ItsMap\Exceptions;

use Exception;

class CenterDistanceFarAway extends Exception
{

    public function __construct($version1, $version2, $distance)
    {
        parent::__construct();

        $this->message = "两个版本 {$version1}, {$version2} 集合中心点距离 {$distance} 超过限制";
    }


    protected $code = 10013;

}