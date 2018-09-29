<?php

namespace Didi\Cloud\ItsMap;

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Contracts\CityInterface;
use Didi\Cloud\ItsMap\Contracts\FlowInterface;
use Didi\Cloud\ItsMap\Exceptions\FlowDuplicateExist;
use Didi\Cloud\ItsMap\Models\City as CityModel;
use Didi\Cloud\ItsMap\Models\FlowLogic;
use Didi\Cloud\ItsMap\Services\RoadNet;
use Didi\Cloud\ItsMap\Supports\Coordinate;
use Didi\Cloud\ItsMap\Supports\FlowShift;

class Flow implements FlowInterface
{
    public function __construct()
    {
        Env::init();
        MapManager::bootEloquent();
    }

    public function find($flowId, $version)
    {
        $flowLogic = FlowLogic::where('logic_flow_id', $flowId)->rangeVersion($version)->first();

        return $flowLogic->toArray();
    }

    public function allByJunction($junctionId, $version)
    {
        $flowLogicMaps = FlowLogic::where('logic_junction_id', $junctionId)->rangeVersion($version)->get();

        return $flowLogicMaps->toArray();
    }

    public function allByJunctions($junctionIds, $version)
    {
        $flowLogicMaps = FlowLogic::whereIn('logic_junction_id', $junctionIds)->rangeVersion($version)->get();

        return $flowLogicMaps->toArray();
    }

    public function findFlowByInoutLink($inLink, $outLink, $version)
    {
        // TODO: Implement findFlowByInoutLink() method.
    }

    public function findFlowsByInoutLink($inOutLinkPairs, $version)
    {
        // TODO: Implement findFlowsByInoutLink() method.
    }

    public function findByMainNodeIdInoutLink($mainNodeId, $inLink, $outLink, $version)
    {
        $flows = FlowLogic::where('simple_main_node_id', $mainNodeId)->where('start_version', "<=", $version)->where('end_version', '>', $version)->where('inlink', $inLink)->where('outlink', $outLink)->get();

        if (count($flows) >= 2) {
            throw new FlowDuplicateExist();
        }

        if (count($flows) == 0) {
            return [];
        }


        return $flows->first()->toArray();
    }

    public function allByJunctionWithLinkAttr($logicJunctionId, $version)
    {
        $flows = FlowLogic::where('logic_junction_id', $logicJunctionId)->rangeVersion($version)->get();
        $inLinkIds = $flows->pluck('inlink')->unique()->all();
        $outLinkIds = $flows->pluck('outlink')->unique()->all();

        $linkIds = array_merge($inLinkIds, $outLinkIds);

        $roadNet = new RoadNet();
        $links = $roadNet->linkQuery($version, $linkIds);

        $linkIdKeys = [];
        foreach ($links as $link) {
            $linkIdKeys[$link->link_id] = [
                's_node' => [
                    'node_id' => $link->s_node->node_id,
                    'lng' => Coordinate::formatManual($link->s_node->lng),
                    'lat' => Coordinate::formatManual($link->s_node->lat),
                ],
                'e_node' => [
                    'node_id' => $link->e_node->node_id,
                    'lng' => Coordinate::formatManual($link->e_node->lng),
                    'lat' => Coordinate::formatManual($link->e_node->lat),
                ],
            ];
        }

        $ret = [];
        foreach ($flows as $flow) {
            $tmp = $flow->toArray();
            $inLinkId = $tmp['inlink'];
            $outLinkId = $tmp['outlink'];
            $tmp['inlink_info'] = $linkIdKeys[$inLinkId];
            $tmp['outlink_info'] = $linkIdKeys[$outLinkId];
            $ret[] = $tmp;
        }
        return $ret;
    }

    public function simplifyFlows($logic_junction_id, $version, $logic_flow_ids)
    {
        if ($logic_flow_ids === NULL or $logic_flow_ids === '' or empty($logic_flow_ids)) {
            $flowLogicMaps = FlowLogic::where('logic_junction_id', $logic_junction_id)->rangeVersion($version)->get();
            $flows = $flowLogicMaps->toArray();
        } else {
            // $logic_flow_ids = explode(',', $logic_flow_ids);
            $flowLogicMaps = FlowLogic::whereIn('logic_flow_id', $logic_flow_ids)->rangeVersion($version)->get();
            $flows = $flowLogicMaps->toArray();
        }
        if (empty($flows)) {
            return array();
        }
        $flowShift = new FlowShift();
        return $flowShift->func($flows, $version);
    }
}