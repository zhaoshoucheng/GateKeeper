<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class FlagVersionMustInVersionNodeIds extends Exception
{
    protected $code = 10018;

    protected $message = "旗帜版本必须在版本节点列表中存在";
}