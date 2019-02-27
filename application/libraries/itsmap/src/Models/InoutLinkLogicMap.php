<?php

namespace Didi\Cloud\ItsMap\Models;

use Didi\Cloud\ItsMap\Junction;
use Didi\Cloud\ItsMap\Supports\DiffTrait;
use Illuminate\Database\Eloquent\Collection;

class InoutLinkLogicMap extends \Illuminate\Database\Eloquent\Model
{
    use DiffTrait;

    protected $table = "inout_link_logic_map";

    protected function uniqFields()
    {
        return ['logic_link_id', 'link_id', 'logic_junction_id', 'start_version', 'end_version'];
    }

    protected function updateFields()
    {
        return ['simple_main_node_id', 'degree'];
    }


}