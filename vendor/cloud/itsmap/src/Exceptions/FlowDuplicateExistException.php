<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class FlowDuplicateExistException extends Exception
{
    protected $code = 10007;

    protected $message = "Flow在两个简单路口存在";
}