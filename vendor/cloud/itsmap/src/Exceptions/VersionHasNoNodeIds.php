<?php

namespace Didi\Cloud\ItsMap\Exceptions;

use Exception;

class VersionHasNoNodeIds extends Exception
{
    protected $code = 10019;

    public function __construct($version)
    {
        parent::__construct();
        $this->message = "版本 {$version} 中没有相应节点";
    }
}