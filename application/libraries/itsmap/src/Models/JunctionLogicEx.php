<?php

namespace Didi\Cloud\ItsMap\Models;

use Didi\Cloud\ItsMap\Exceptions\Exception;
use Didi\Cloud\ItsMap\Exceptions\NodeNotExistException;
use Didi\Cloud\ItsMap\Junction;
use Didi\Cloud\ItsMap\Supports\Coordinate;
use Didi\Cloud\ItsMap\Supports\DiffTrait;

class JunctionLogicEx extends \Illuminate\Database\Eloquent\Model
{
    use DiffTrait;

    protected $table = "junction_logic_ex";

    protected function uniqFields()
    {
        return ['logic_junction_id'];
    }

    protected function updateFields()
    {
        return ['version'];
    }
}