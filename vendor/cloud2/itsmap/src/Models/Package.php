<?php

namespace Didi\Cloud\ItsMap\Models;

/*
 * 一个节点的打包，能通过这个Package找出节点所有的交通路网信息
 */
use Didi\Cloud\ItsMap\Exceptions\CenterDistanceFarAway;
use Didi\Cloud\ItsMap\Exceptions\NodeExistInOtherJunction;
use Didi\Cloud\ItsMap\Exceptions\NodeNotExistInVersion;
use Didi\Cloud\ItsMap\Exceptions\VersionsNotContinuityException;
use Didi\Cloud\ItsMap\Services\RoadNet;
use Didi\Cloud\ItsMap\Supports\Coordinate;
use Illuminate\Database\Eloquent\Collection;

class Package
{
    // junction_logic
    public $junctionLogic;

    // junction_logic_ex
    public $junctionLogicEx;

    // junction_logic_map
    public $junctionLogicMaps;

    // inout_link_logic
    public $inoutLinkLogics;

    // inout_link_logic_map
    public $inoutLinkLogicMaps;

    // flow_logic_map
    public $flowLogicMaps;

    public function __construct($junctionLogic, $junctionLogicEx, $junctionLogicMaps, $inoutLinkLogics, $inoutLinkLogicMaps, $flowLogicMaps)
    {
        $this->junctionLogic = $junctionLogic;
        $this->junctionLogicMaps = $junctionLogicMaps;
        $this->inoutLinkLogics = $inoutLinkLogics;
        $this->inoutLinkLogicMaps = $inoutLinkLogicMaps;
        $this->flowLogicMaps = $flowLogicMaps;
        $this->junctionLogicEx = $junctionLogicEx;
        return $this;
    }

    /**
     * 生成Package
     *
     * @param $junctionLogic
     * @param $versionNodeIds
     * @param string $flagVersion
     * @return Package
     * @throws CenterDistanceFarAway
     * @throws NodeExistInOtherJunction
     * @throws NodeNotExistInVersion
     * @throws VersionsNotContinuityException
     */
    public static function generate($junctionLogic, $versionNodeIds, $flagVersion, $oldJunctionLogicId = "")
    {
        // 传递的版本必须是连续的
        $versions = array_keys($versionNodeIds);
        if (!Version::isContinuity($versions)) {
            throw new VersionsNotContinuityException();
        }

        $nodeModel = new Node();
        $versionNodes = $nodeModel->versionNodes($versionNodeIds);

        //生成的versionNodes的个数（version个数 和 nodeIds）
        foreach ($versionNodes as $version => $nodes) {
            $nodeIds = $versionNodeIds[$version];
            $dbNodeIds = $nodes->pluck('node_id')->unique();
            $lessNodeIds = array_diff($nodeIds, $dbNodeIds);
            if ($lessNodeIds) {
                throw new NodeNotExistInVersion($lessNodeIds[0], $version);
            }
        }

        // 传递的版本的NodeId的集合的集合中心节点小于200米
        $centerGps = [];
        foreach ($versionNodes as $version => $nodes) {
            $latLngs = $nodes->map(function($item) {return ['lat' => Coordinate::formatManual($item->lat), 'lng' => Coordinate::formatManual($item->lng)];})->all();
            $centerGps[] = [
                'version' => $version,
                'gps' => Coordinate::geometric($latLngs),
            ];

        }
        for ($i = 0; $i < count($centerGps); $i++) {
            $versionGpsI = $centerGps[$i];
            for ($j = $i; $j < count($centerGps); $j++) {
                $versionGpsJ = $centerGps[$j];

                if ($versionGpsI['version'] == $versionGpsJ['version']) {
                    continue;
                }

                $distance = Coordinate::distance(
                    $versionGpsI['gps']['lat'],
                    $versionGpsI['gps']['lng'],
                    $versionGpsJ['gps']['lat'],
                    $versionGpsJ['gps']['lng']
                );
                if ($distance > 200) {
                    throw new CenterDistanceFarAway($versionGpsI['version'], $versionGpsJ['version'], $distance);
                }
            }
        }

        // nodeIds 没有被附近2公里某个路口占用
        $gradIndex1000Ids = Coordinate::gridIndex(Coordinate::formatManual($junctionLogic->lat), Coordinate::formatManual($junctionLogic->lng), 1000);
        $nearJunctionLogics = JunctionLogic::available()->whereIn('grid_index_1000', $gradIndex1000Ids)->get();
        $nearJunctionLogics->load('maps');

        foreach ($nearJunctionLogics as $nearJunctionLogic) {
            // 当前的junctionLogicId就不检查了
            if ($nearJunctionLogic->logic_junction_id == $junctionLogic->logic_junction_id) {
                continue;
            }

            if ($nearJunctionLogic->logic_junction_id == $oldJunctionLogicId) {
                continue;
            }

            foreach ($nearJunctionLogic->maps as $junctionLogicMap) {
                $versions = Version::range($junctionLogicMap->start_version, $junctionLogicMap->end_version);
                $dbNodeIds = $junctionLogicMap->node_ids;
                $dbNodeIds = explode(',', $dbNodeIds);
                foreach ($versions as $version) {
                    $newNodeIds = $versionNodeIds[$version];
                    $commonNodeIds = array_intersect($newNodeIds, $dbNodeIds);

                    if ($commonNodeIds) {
                        throw new NodeExistInOtherJunction($commonNodeIds[0], $version, $nearJunctionLogic->logic_junction_id);
                    }
                }
            }
        }

        $junctionLogicEx = new JunctionLogicEx();
        $junctionLogicEx->version = $flagVersion;
        $junctionLogicEx->logic_junction_id = $junctionLogic->logic_junction_id;


        $inheritVersion = $flagVersion;

        $roadNetService = new RoadNet();
        $inheritVersionRes = $roadNetService->inoutLinkGenerate($junctionLogic->logic_junction_id, $inheritVersion, $versionNodeIds[$inheritVersion]);

        $inheritVersionId = $inheritVersionRes->version_id;

        $inheritNodeIds = $versionNodeIds[$inheritVersionId];
        unset($versionNodeIds[$inheritVersionId]);
        $otherVersions = $roadNetService->inoutLinkInhert($junctionLogic->logic_junction_id, $versionNodeIds, $inheritVersionId, $inheritNodeIds, $inheritVersionRes->inout_link);

        $allJunctionInoutLinkRes = array_merge([$inheritVersionRes], $otherVersions);

        // 重新组织
        $inoutLinkLogics = [];
        $inoutLinkLogicMaps = [];
        $flowLogicMaps = [];
        $versionInnerLinks = [];
        foreach ($allJunctionInoutLinkRes as $junctionInoutLinkRe) {
            $version = $junctionInoutLinkRe->version_id;
            $inoutLinkRes = $junctionInoutLinkRe->inout_link;
            $flowRes = $junctionInoutLinkRe->flow;
            $versionInnerLinks[$version] = $junctionInoutLinkRe->inner_links;

            $inoutLinkKeyRes = [];
            foreach ($inoutLinkRes as $inoutLinkRe) {
                $inoutLinkKeyRes[$inoutLinkRe->link_id] = $inoutLinkRe;
            }

            // 组织inoutLink
            foreach ($inoutLinkRes as $inoutLinkRe) {
                $logicInoutLinkId = $inoutLinkRe->logic_inout_link_id;
                $linkId = $inoutLinkRe->link_id;
                $inoutLinkFlag = $inoutLinkRe->inout_link_flag;
                $dbInoutFlag = 0;
                if ($inoutLinkFlag == 0) {
                    $dbInoutFlag = 1;
                } else if ($inoutLinkFlag == 1) {
                    $dbInoutFlag = 2;
                }
                $level = $inoutLinkRe->level;
                $degree = $inoutLinkRe->degree;

                if ($level == 3) {
                    // 根据level 判断是否要添加，如果无法继承，则需要添加
                    $inoutLinkLogic = new InoutLinkLogic();
                    $inoutLinkLogic->logic_junction_id = $junctionLogic->logic_junction_id;
                    $inoutLinkLogic->logic_link_id = $logicInoutLinkId;
                    $inoutLinkLogic->inout_flag = $dbInoutFlag;
                    $inoutLinkLogic->degree = $degree;
                    $inoutLinkLogics[] = $inoutLinkLogic;
                }

                // 所有都需要增加到map上
                $inoutLinkLogicMap  = new InoutLinkLogicMap();
                $inoutLinkLogicMap->logic_link_id = $logicInoutLinkId;
                $inoutLinkLogicMap->simple_main_node_id = 0;
                $inoutLinkLogicMap->link_id = $linkId;
                $inoutLinkLogicMap->logic_junction_id = $junctionLogic->logic_junction_id;
                $inoutLinkLogicMap->start_version = $version;
                $inoutLinkLogicMap->end_version = Version::nextVersion($version);
                $inoutLinkLogicMap->degree = $degree;
                $inoutLinkLogicMaps[] = $inoutLinkLogicMap;
            }

            // 组织flow，每个flow都应该在flow_map表里面有一个数据
            foreach ($flowRes as $flowRe) {
                $inLinkId = $flowRe->in_link_id;
                $outLinkId = $flowRe->out_link_id;
                $turnDegree = $flowRe->turn_degree;

                $inLinkLogic = $inoutLinkKeyRes[$inLinkId];
                $outLinkLogic = $inoutLinkKeyRes[$outLinkId];
                $inLinkLogicId = $inLinkLogic->logic_inout_link_id;
                $outLinkLogicId = $outLinkLogic->logic_inout_link_id;

                $flowLogicMap = new FlowLogic();
                $flowLogicMap->logic_flow_id = FlowLogic::genComplexLogicId($version, $inLinkLogicId, $outLinkLogicId);
                $flowLogicMap->logic_junction_id = $junctionLogic->logic_junction_id;
                $flowLogicMap->simple_main_node_id = 0;
                $flowLogicMap->inlink = $inLinkId;
                $flowLogicMap->outlink = $outLinkId;
                $flowLogicMap->in_logic_link_id = $inLinkLogicId;
                $flowLogicMap->out_logic_link_id = $outLinkLogicId;
                $flowLogicMap->indegree = $inLinkLogic->degree;
                $flowLogicMap->outdegree = $outLinkLogic->degree;
                $flowLogicMap->turn_degree = $turnDegree;
                $flowLogicMap->start_version = $version;
                $flowLogicMap->end_version = Version::nextVersion($version);

                $flowLogicMaps[] = $flowLogicMap;
            }
        }

        // 操作LogicMap表
        $junctionLogicMaps = JunctionLogicMap::instanceJunctionLogicMap($junctionLogic, $versionNodes, $versionInnerLinks);

        return new Package($junctionLogic, $junctionLogicEx, $junctionLogicMaps, $inoutLinkLogics, $inoutLinkLogicMaps, $flowLogicMaps);
    }

    // 获取数据库中的Package
    public static function getFromDB($junctionLogic)
    {
        // 操作LogicMap表
        $junctionLogicEx = $junctionLogic->logicEx;
        $junctionLogicMaps = $junctionLogic->maps;
        $inoutLinkLogics = InoutLinkLogic::where('logic_junction_id', $junctionLogic->logic_junction_id)->get();
        $inoutLinkLogicMaps = InoutLinkLogicMap::where('logic_junction_id', $junctionLogic->logic_junction_id)->get();
        $flowLogicMaps = FlowLogic::where('logic_junction_id', $junctionLogic->logic_junction_id)->get();

        return new Package($junctionLogic, $junctionLogicEx, $junctionLogicMaps, $inoutLinkLogics, $inoutLinkLogicMaps, $flowLogicMaps);
    }


    /*
     * 将当前的package 和 newPackage 进行比较
     */
    public function diff(Package $newPackage)
    {
        $toAdd = [];
        $toDel = [];
        $toUpdate = [];

        // junctionLogic
        $result = self::compareToUpdate($this->junctionLogic, $newPackage->junctionLogic);
        if($result) {
            $toUpdate[] = $result;
        }

        // junctionLogicEx
        if (empty($newPackage->junctionLogicEx)) {
            if (!empty($this->junctionLogicEx)) {
                $toDel[] = $this->junctionLogicEx;
            }
        } else {
            if (empty($this->junctionLogicEx)) {
                $toAdd[] = $this->junctionLogicEx;
            } else {
                $result = self::compareToUpdate($this->junctionLogicEx, $newPackage->junctionLogicEx);
                if ($result) {
                    $toUpdate[] = $result;
                }
            }
        }

        $result = self::fullCompare($this->junctionLogicMaps, $newPackage->junctionLogicMaps);
        $toAdd = array_merge($toAdd, $result['toAdd']);
        $toDel = array_merge($toDel, $result['toDel']);
        $toUpdate = array_merge($toUpdate, $result['toUpdate']);

        $result = self::fullCompare($this->inoutLinkLogics, $newPackage->inoutLinkLogics);
        $toAdd = array_merge($toAdd, $result['toAdd']);
        $toDel = array_merge($toDel, $result['toDel']);
        $toUpdate = array_merge($toUpdate, $result['toUpdate']);

        $result = self::fullCompare($this->inoutLinkLogicMaps, $newPackage->inoutLinkLogicMaps);
        $toAdd = array_merge($toAdd, $result['toAdd']);
        $toDel = array_merge($toDel, $result['toDel']);
        $toUpdate = array_merge($toUpdate, $result['toUpdate']);

        $result = self::fullCompare($this->flowLogicMaps, $newPackage->flowLogicMaps);
        $toAdd = array_merge($toAdd, $result['toAdd']);
        $toDel = array_merge($toDel, $result['toDel']);
        $toUpdate = array_merge($toUpdate, $result['toUpdate']);

        return compact('toAdd', 'toDel', 'toUpdate');
    }

    private static function compareToUpdate($oldModel, $newModel)
    {
        $toUpdate = null;
        if ($oldModel->uniq() == $newModel->uniq()) {
            if ($oldModel->isNeedUpdate($newModel)) {
                $toUpdate = $oldModel->update($newModel);
            }
        }
        return $toUpdate;
    }

    private static function fullCompare($oldCollection, $newCollection)
    {
        $toAdd = [];
        $toDel = [];
        $toUpdate = [];

        $oldCollection = Collection::make($oldCollection);
        $newCollection = Collection::make($newCollection);

        $oldCollection = $oldCollection->sortBy(function($model) {
            return $model->uniq();
        });

        $newCollection = $newCollection->sortBy(function($model) {
            return $model->uniq();
        });

        $oldI = 0;
        $newI = 0;

        while(1) {
            if ($oldCollection->offsetExists($oldI) == false && $newCollection->offsetExists($newI) == false) {
                break;
            }

            if ($oldCollection->offsetExists($oldI) == false) {
                $toAdd[] = $newCollection->offsetGet($newI);
                $newI++;
                continue;
            }

            if ($newCollection->offsetExists($newI) == false) {
                $toDel[] = $oldCollection->offsetGet($oldI);
                $oldI++;
                continue;
            }

            $oldModel = $oldCollection->offsetGet($oldI);
            $newModel = $newCollection->offsetGet($newI);

            if ($oldModel->uniq() > $newModel->uniq()) {
                $toAdd[] = $newModel;
                $newI++;
                continue;
            }

            if ($oldModel->uniq() < $newModel->uniq()) {
                $toDel[] = $oldModel;
                $oldI++;
                continue;
            }

            if ($oldModel->uniq() == $newModel->uniq()) {
                if ($oldModel->isNeedUpdate($newModel)) {
                    $toUpdate[] = $oldModel->update($newModel);
                }

                $newI++;
                $oldI++;
                continue;
            }
        }

        return compact('toAdd', 'toDel', 'toUpdate');
    }

}