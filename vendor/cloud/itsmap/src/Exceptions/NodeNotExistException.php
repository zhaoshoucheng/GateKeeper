<?php

namespace Didi\Cloud\ItsMap\Exceptions;

class NodeNotExistException extends Exception
{
    protected $code = 10003;

    protected $message = "Node不存在";
}