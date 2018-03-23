<?php

namespace Didi\Cloud\ItsMap\Models;

use Didi\Cloud\ItsMap\Supports\DiffTrait;
use Didi\Cloud\ItsMap\Junction;
use Illuminate\Database\Eloquent\Collection;

class InoutLinkLogic extends \Illuminate\Database\Eloquent\Model
{
    use DiffTrait;

    protected $table = "inout_link_logic";

    const FLAG_IN = 1; // 进入link 的 flag
    const FLAG_OUT = 2; // 出link 的 flag


    protected function uniqFields()
    {
        return ['logic_junction_id', 'logic_link_id', 'inout_flag'];
    }

    protected function updateFields()
    {
        return ['degree'];
    }

    /*
     * 生成InoutLink的逻辑ID
     */
    public static function genComplexLogicId($linkId, $inoutFlag, $version, $logicJunctionId)
    {
        $prefix = $inoutFlag == self::FLAG_IN ? "i" : "o";
        return md5("{$version}_{$prefix}_{$linkId}_{$logicJunctionId}");
    }

}