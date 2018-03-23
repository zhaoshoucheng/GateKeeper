<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class ItsMapThriftFormatError extends Exception
{
    protected $code = 10004;

    protected $message = "调用ItsMap错误";
}