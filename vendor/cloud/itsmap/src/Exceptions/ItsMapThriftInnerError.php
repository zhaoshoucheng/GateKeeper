<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class ItsMapThriftInnerError extends Exception
{
    protected $code = 10005;

    protected $message = "调用ItsMap内部错误";
}