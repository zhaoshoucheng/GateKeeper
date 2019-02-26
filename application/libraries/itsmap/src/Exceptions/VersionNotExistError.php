<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class VersionNotExistError extends Exception
{
    protected $code = 10009;

    protected $message = "版本不存在";
}