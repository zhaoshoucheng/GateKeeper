<?php

namespace Didi\Cloud\ItsMap;

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Contracts\NodeInterface;
use Didi\Cloud\ItsMap\Exceptions\MainNodeNotExistException;
use Didi\Cloud\ItsMap\Exceptions\NodeNotExistException;
use Didi\Cloud\ItsMap\Models\Version;
use Didi\Cloud\ItsMap\Services\RoadNet;
use Didi\Cloud\ItsMap\Supports\Arr;
use Didi\Cloud\ItsMap\Supports\Coordinate;
use Didi\Cloud\ItsMap\Models\Node as NodeModel;


class Node implements NodeInterface
{
    public function __construct()
    {
        Env::init();
        MapManager::bootEloquent();
    }

    public function nodes($nodeIds, $version)
    {
        $nodeModel = new NodeModel();
        return $nodeModel->nodes($version, $nodeIds)->toArray();
    }

    public function regionNodes($cityId, $version, $lat, $lng, $distance = 100)
    {
        $gradIndex100Ids = Coordinate::gridIndex100($lat, $lng);
        $nodes = NodeModel::whereIn('grid_index_100', $gradIndex100Ids)->where('start_version', '<=', $version)->where('end_version', '>', $version)->where('city_id', $cityId)->get();
        $nodes = $nodes->filter(function($node) use($lat, $lng, $distance){
            return Coordinate::distance($node->lat_real, $node->lng_real, $lat, $lng) < $distance;
        });
        return $nodes->toArray();
    }

    public function subNodes($mainNodeId, $version)
    {
        $nodes = NodeModel::whereIn('main_node_id', $mainNodeId)->where('start_version', '<=', $version)->where('end_version', '>', $version)->get();
        return $nodes->toArray();
    }


    public function mainNode($nodeId, $version)
    {
        $node = NodeModel::whereIn('node_id', $nodeId)->where('start_version', '<=', $version)->where('end_version', '>', $version)->first();
        if (empty($node)) {
            throw new NodeNotExistException();
        }

        $mainNode = NodeModel::whereIn('node_id', $node->main_node_id)->where('start_version', '<=', $version)->where('end_version', '>', $version)->first();
        if (empty($mainNode)) {
            throw new MainNodeNotExistException();
        }
        return $mainNode->toArray();
    }

    public function versions()
    {
        return Version::versions();
    }

    public function suggest($cityId, $version, $nodeIds, $toVersions)
    {
        $roadNetService = new RoadNet();

        $toVersions[] = $version;
        sort($toVersions);
        $key = array_search($version, $toVersions);

        $oldVersions = array_reverse(array_slice($toVersions, 0, $key));
        $newVersions = array_slice($toVersions, $key + 1);

        if ($oldVersions) {
            $response = $roadNetService->nodeInheritProcess($version, $nodeIds, $oldVersions);

            $junctionResAll = [];
            foreach ($response->res as $listJunctionRes) {
                $version = $listJunctionRes->version_id;
                foreach ($listJunctionRes->junctions as $junctionRes) {
                    $junctionResAll[] = [
                        'version' => $version,
                        'level' => $junctionRes->level,
                        'node_ids' => Arr::arr2str($junctionRes->node_id),
                    ];
                }
            }
        }

        if ($newVersions) {
            $response = $roadNetService->nodeInheritProcess($version, $nodeIds, $newVersions);

            foreach ($response->res as $listJunctionRes) {
                $version = $listJunctionRes->version_id;
                foreach ($listJunctionRes->junctions as $junctionRes) {
                    $junctionResAll[] = [
                        'version' => $version,
                        'level' => $junctionRes->level,
                        'node_ids' => Arr::arr2str($junctionRes->node_id),
                    ];
                }
            }
        }


        return $junctionResAll;
    }

    public function mainNodeIds($logicJunctionId, $version)
    {

    }

}