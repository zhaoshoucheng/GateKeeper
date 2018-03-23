<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class NodeExistInOtherJunction extends Exception
{
    protected $code = 10015;

    public function __construct($nodeId, $version, $otherLogicJunctionId)
    {
        parent::__construct();

        $this->message = "Node {$nodeId} 在版本 {$version} 已经和路口 {$otherLogicJunctionId} 关联，一个Node 同一个版本不能在两个路口中存在";
    }
}