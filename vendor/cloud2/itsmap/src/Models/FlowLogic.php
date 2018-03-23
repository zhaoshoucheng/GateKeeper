<?php

namespace Didi\Cloud\ItsMap\Models;

use Didi\Cloud\ItsMap\Supports\DiffTrait;
use Illuminate\Database\Eloquent\Collection;

class FlowLogic extends \Illuminate\Database\Eloquent\Model
{
    use DiffTrait;

    protected $table = "flow_logic_map";
    
    protected function uniqFields()
    {
        return ['logic_flow_id', 'logic_junction_id', 'inlink', 'outlink', 'in_logic_link_id', 'out_logic_link_id', 'start_version', 'end_version'];
    }

    protected function updateFields()
    {
        return ['simple_main_node_id', 'indegree', 'outdegree', 'turn_degree'];
    }

    /*
     * 版本范围
     */
    public function scopeRangeVersion($query, $version)
    {
        return $query->where('start_version', '<=', $version)->where('end_version', '>', $version);
    }

    /*
     * 生成InoutLink的逻辑ID
     */
    public static function genComplexLogicId($version, $inLogicId, $outLogicId)
    {
        return md5("{$inLogicId}_{$outLogicId}");
    }

    public function toArray()
    {
        return [
            'logic_flow_id' => $this->logic_flow_id,
            'logic_junction_id' => $this->logic_junction_id,
            'simple_main_node_id' => $this->simple_main_node_id,
            'inlink' => $this->inlink,
            'outlink' => $this->outlink,
            'indegree' => $this->indegree,
            'outdegree' => $this->outdegree,
            'turn_degree' => $this->turn_degree,
            'created_at' => strval($this->created_at),
            'updated_at' => strval($this->updated_at),
        ];
    }
}