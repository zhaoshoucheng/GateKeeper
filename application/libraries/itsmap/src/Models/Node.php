<?php

namespace Didi\Cloud\ItsMap\Models;

use Didi\Cloud\ItsMap\Supports\Coordinate;
use Illuminate\Database\Eloquent\Collection;

class Node extends \Illuminate\Database\Eloquent\Model
{
    protected $table = "its_mapdata_node_attr";

    public function getLatRealAttribute()
    {
        return Coordinate::formatManual($this->lat);
    }

    public function getLngRealAttribute()
    {
        return Coordinate::formatManual($this->lng);
    }

    /*
     * 判断version版本
     */
    public function scopeVersionInRange($query, $version)
    {
        return $query->where('start_version', "<=", $version)->where('end_version', '>', $version);
    }

    /*
     * 根据version和NodeIds获取信息
     */
    public function nodes($version, $nodeIds)
    {
        return $this->whereIn('node_id', $nodeIds)->versionInRange($version)->get();
    }

    /*
     * 根据['version' => $nodes] 来获取每个节点的信息
     *
     * @params $versionNodes ['version' => $nodes] 版本号和nodes的映射
     *
     * @return ['versions' => collect(Models\Node)]
     */
    public function versionNodes($versionNodeIds)
    {
        $allNodeIds = [];
        foreach ($versionNodeIds as $version => $nodeIds) {
            $allNodeIds = array_merge($allNodeIds, $nodeIds);
        }
        $allNodeIds = array_unique($allNodeIds);

        $query = $this->whereIn('node_id', $allNodeIds);

        foreach ($versionNodeIds as $version => $nodeIds) {
            $query = $this->orWhereRaw("node_id in (". implode(',', $nodeIds) .") and start_version <= {$version} and end_version > {$version}");
        }

        $mapNodes = $query->get();
        $ret = [];

        foreach ($versionNodeIds as $version => $nodeIds) {
            $collectNodes = [];
            foreach ($nodeIds as $nodeId) {
                // 查找符合的mapNodes
                foreach ($mapNodes as $mapNode) {
                    if ($mapNode->node_id == $nodeId && $mapNode->start_version <= $version && $mapNode->end_version > $version) {
                        $collectNodes[] = $mapNode;
                        break;
                    }
                }
            }
            $ret[$version] = Collection::make($collectNodes);
        }
        return $ret;
    }

    public function toArray()
    {
        return [
            'lat' => $this->lat_real,
            'lng' => $this->lng_real,
            'id' => $this->id,
            'node_id' => $this->node_id,
            'cross_flag' => $this->cross_flag,
            'light_flag' => $this->light_flag,
            'main_node_id' => $this->main_node_id,
            'is_mainNode' => in_array($this->cross_flag, [2, 3]),
        ];
    }
}