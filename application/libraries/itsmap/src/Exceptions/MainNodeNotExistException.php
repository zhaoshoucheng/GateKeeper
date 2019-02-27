<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class MainNodeNotExistException extends Exception
{
    protected $code = 10002;

    protected $message = "主Node不存在";
}