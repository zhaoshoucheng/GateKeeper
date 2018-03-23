<?php

namespace Didi\Cloud\ItsMap\Exceptions;

use Exception;

class VersionsNotContinuityException extends Exception
{
    protected $code = 10012;

    protected $message = "版本不是连续的";

}