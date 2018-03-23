<?php

namespace Didi\Cloud\ItsMap\Models;

use Didi\Cloud\ItsMap\Supports\Coordinate;
use Didi\Cloud\ItsMap\Supports\DiffTrait;
use Illuminate\Database\Eloquent\Collection;

class JunctionLogicMap extends \Illuminate\Database\Eloquent\Model
{
    use DiffTrait;

    protected $table = "junction_logic_map";

    protected function uniqFields()
    {
        return ['logic_junction_id', 'node_ids', 'start_version', 'end_version'];
    }

    protected function updateFields()
    {
        return ['simple_main_node_id', 'inner_link_ids', 'lng', 'lat'];
    }

    /*
     * 版本范围
     */
    public function scopeRangeVersion($query, $version)
    {
        return $query->where('start_version', '<=', $version)->where('end_version', '>', $version);
    }

    public function toArray()
    {
        return [
            'logic_junction_id' => $this->logic_junction_id,
            'node_ids' => $this->node_ids,
            'simple_main_node_id' => $this->simple_main_node_id,
            'inner_link_ids' => $this->inner_link_ids,
            'lng' => Coordinate::formatManual($this->lng),
            'lat' => Coordinate::formatManual($this->lat),
            'start_version' => $this->start_version,
            'end_version' => $this->end_version,
            'created_at' => strval($this->created_at),
            'updated_at' => strval($this->updated_at),
        ];
    }

    /*
     * 通过versionNodes 来获取junctionLogicMap
     */
    public static function instanceJunctionLogicMap($junctionLogic, $versionNodes, $versionInnerLinks)
    {
        $ret = [];
        foreach ($versionNodes as $version => $nodes) {
            $nodeIds = $nodes->pluck('node_id')->sort()->all();
            $latLngs = $nodes->map(function($node) {
                return [
                    'lat' => Coordinate::formatManual($node->lat),
                    'lng' => Coordinate::formatManual($node->lng)
                ] ;
            })->all();
            list($lat, $lng) = array_values(Coordinate::geometric($latLngs));
            $simpleNodeId = 0;
            $mainNodeIds = $nodes->pluck('main_node_id')->unique()->values()->all();
            if (count($mainNodeIds) == 1) {
                $simpleNodeId = array_first($mainNodeIds);
            }

            $junctionLogicMap = new JunctionLogicMap();
            $junctionLogicMap->logic_junction_id = $junctionLogic->logic_junction_id;
            $junctionLogicMap->node_ids = implode(',', $nodeIds);
            $junctionLogicMap->simple_main_node_id = intval($simpleNodeId);
            $junctionLogicMap->inner_link_ids = implode(',', $versionInnerLinks[$version]);
            $junctionLogicMap->lat = Coordinate::formatDb($lat);
            $junctionLogicMap->lng = Coordinate::formatDb($lng);
            $junctionLogicMap->start_version = $version;
            $junctionLogicMap->end_version = Version::nextVersion($version);
            $ret[] = $junctionLogicMap;
        }
        return $ret;
    }

    /*
     * 按照versions展开所有的maps
     *
     * @params Collect(JunctionLogicMap) $maps JunctionLogicMap表数据
     *
     * @return [string => [string, lat, lng]] [version => [nodes, lat, lng]] 每个versions都对应
     */
    public static function toAllVersionMaps($maps)
    {
        $versions = Version::versions();

        $ret = [];
        foreach ($versions as $version) {
            // 查找这个version所属的哪个map
            foreach ($maps as $map) {
                if ($version >= $map->start_version && $version < $map->end_version) {
                    $ret[] = [
                        'node_ids' => $map->node_ids,
                        'lat' => Coordinate::formatManual($map->lat),
                        'lng' => Coordinate::formatManual($map->lng),
                        'version' => $version,
                    ];
                    break;
                }
            }
        }
        return $ret;
    }
}