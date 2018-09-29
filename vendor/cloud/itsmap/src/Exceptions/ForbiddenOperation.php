<?php

namespace Didi\Cloud\ItsMap\Exceptions;

use Exception;

class ForbiddenOperation extends Exception
{

    public function __construct($operation)
    {
        parent::__construct();

        $this->message = "该操作 {$operation} 已经被禁用";
    }


    protected $code = 10011;

}