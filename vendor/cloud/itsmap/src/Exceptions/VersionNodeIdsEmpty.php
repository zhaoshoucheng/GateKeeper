<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class VersionNodeIdsEmpty extends Exception
{
    protected $code = 10017;

    protected $message = "版本节点列表不能为空";
}