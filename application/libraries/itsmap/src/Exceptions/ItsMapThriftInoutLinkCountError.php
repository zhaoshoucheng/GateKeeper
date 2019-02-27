<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class ItsMapThriftInoutLinkCountError extends Exception
{
    protected $code = 10008;

    protected $message = "调用ItsMap内部, 返回的InoutLink个数不对";
}