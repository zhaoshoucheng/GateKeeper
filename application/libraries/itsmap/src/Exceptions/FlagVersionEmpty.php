<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class FlagVersionEmpty extends Exception
{
    protected $code = 10016;

    protected $message = "旗帜版本不能为空";
}