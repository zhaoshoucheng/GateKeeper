<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class MainNodeDuplicateExist extends Exception
{
    protected $code = 10006;

    protected $message = "主Node在两个简单路口存在";
}