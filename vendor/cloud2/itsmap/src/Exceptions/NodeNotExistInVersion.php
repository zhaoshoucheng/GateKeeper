<?php

namespace Didi\Cloud\ItsMap\Exceptions;

use Exception;

class NodeNotExistInVersion extends Exception
{
    protected $code = 10014;

    public function __construct($nodeId, $version)
    {
        parent::__construct();
        $this->message = "Node {$nodeId} 在版本 {$version} 中不存在";
    }

}