<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class JunctionNotExistException extends Exception
{
    protected $code = 10001;

    protected $message = "路口不存在";
}