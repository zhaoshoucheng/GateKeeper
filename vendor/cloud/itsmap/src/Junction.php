<?php

namespace Didi\Cloud\ItsMap;

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Contracts\JunctionInterface;
use Didi\Cloud\ItsMap\Exceptions\DuplicateMapInOneVesion;
use Didi\Cloud\ItsMap\Exceptions\Exception;
use Didi\Cloud\ItsMap\Exceptions\FlagVersionEmpty;
use Didi\Cloud\ItsMap\Exceptions\FlagVersionMustInVersionNodeIds;
use Didi\Cloud\ItsMap\Exceptions\ForbiddenOperation;
use Didi\Cloud\ItsMap\Exceptions\JunctionNotExistException;
use Didi\Cloud\ItsMap\Exceptions\MainNodeDuplicateExist;
use Didi\Cloud\ItsMap\Exceptions\VersionHasNoNodeIds;
use Didi\Cloud\ItsMap\Exceptions\VersionNodeIdsEmpty;
use Didi\Cloud\ItsMap\Models\FlowLogic;
use Didi\Cloud\ItsMap\Models\InoutLinkLogic;
use Didi\Cloud\ItsMap\Models\InoutLinkLogicMap;
use Didi\Cloud\ItsMap\Models\JunctionLogic;
use Didi\Cloud\ItsMap\Models\JunctionLogicEx;
use Didi\Cloud\ItsMap\Models\JunctionLogicMap;
use Didi\Cloud\ItsMap\Models\Node;
use Didi\Cloud\ItsMap\Models\OperationLog;
use Didi\Cloud\ItsMap\Models\Package;
use Didi\Cloud\ItsMap\Models\Version;
use Didi\Cloud\ItsMap\Supports\Arr;
use Didi\Cloud\ItsMap\Supports\Coordinate;
use Illuminate\Database\Capsule\Manager as DB;

class Junction implements JunctionInterface
{
    public function __construct()
    {
        Env::init();
        MapManager::bootEloquent();
    }

    public function summary($cityId)
    {
        $sum = JunctionLogic::available()->where('is_traffic', 1)->where('city_id', $cityId)->count();
        $simple = JunctionLogic::available()->where('is_traffic', 1)->where('city_id', $cityId)->where('is_complex', 0)->count();
        $simple_auto = JunctionLogic::available()->where('is_traffic', 1)->where('city_id', $cityId)->where('is_complex', 0)->where('is_manual', 0)->count();
        $simple_manual = JunctionLogic::available()->where('is_traffic', 1)->where('city_id', $cityId)->where('is_complex', 0)->where('is_manual', 1)->count();
        $complex = JunctionLogic::available()->where('is_traffic', 1)->where('city_id', $cityId)->where('is_complex', 1)->count();
        return compact('sum', 'simple', 'complex', 'simple_auto', 'simple_manual');
    }

    public function find($logicId)
    {
        $junctionLogic = JunctionLogic::available()->where('logic_junction_id', $logicId)->with('maps')->first();
        if (empty($junctionLogic)) {
            throw new JunctionNotExistException();
        }
        return $junctionLogic->toArrayWithMap();
    }

    public function findByShowId($showId)
    {
        $junctionLogic = JunctionLogic::available()->where('logic_show_id', $showId)->with('maps', "")->first();
        if (empty($junctionLogic)) {
            throw new JunctionNotExistException();
        }
        return $junctionLogic->toArrayWithMap();
    }

    public function all($cityId, $offset, $count)
    {
        return JunctionLogic::available()->where('is_traffic', 1)->where('city_id', $cityId)->skip($offset)->take($count)->get()->toArray();
    }

    public function allWithVersion($cityId, $version, $offset, $count)
    {
        return JunctionLogic::available()->versionInRange($version)->where('is_traffic', 1)->where('city_id', $cityId)->skip($offset)->take($count)->get()->toArray();
    }

    public function many($junctionIds)
    {
        return JunctionLogic::available()->whereIn('logic_junction_id', $junctionIds)->get()->toArray();
    }

    public function add($cityId, $versionNodeIds, $flagVersion, $attributes = [])
    {
        $this->checkVersionNodeIds($versionNodeIds, $flagVersion);

        $junctionLogic = JunctionLogic::newJunction($flagVersion, $versionNodeIds[$flagVersion], $cityId, $attributes);

        $versionIds = array_keys($versionNodeIds);
        $junctionLogic->start_version = min($versionIds);
        $junctionLogic->end_version = Version::nextVersion(max($versionIds));

        $package = Package::generate($junctionLogic, $versionNodeIds, $flagVersion);
        $junctionLogicEx = $package->junctionLogicEx;
        $junctionLogic = $package->junctionLogic;
        $junctionLogicMaps = $package->junctionLogicMaps;
        $inoutLinkLogics = $package->inoutLinkLogics;
        $inoutLinkLogicMaps = $package->inoutLinkLogicMaps;
        $flowLogicMaps = $package->flowLogicMaps;

        // 如果数据库中已经有一个相同名字的logicJunction被软删除了。那么就变成编辑状态。
        $deletedJunctionLogic = JunctionLogic::where('logic_junction_id', $junctionLogic->logic_junction_id)->where('is_deleted', 1)->first();

        if ($deletedJunctionLogic) {

            $oldPackage = Package::getFromDB($deletedJunctionLogic);
            $diff = $oldPackage->diff($package);
            extract($diff);

            DB::beginTransaction();
            try {
                foreach ($toDel as $item) {
                    $item->delete();
                }

                foreach($toAdd as $item) {
                    $item->save();
                }

                foreach ($toUpdate as $item) {
                    $item->save();
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            OperationLog::logAdd($oldPackage, $package, OperationLog::ADDJUNCTION);
            return $junctionLogic->toArray();

        } else {

            DB::beginTransaction();
            try {

                // 操作Logic表： 1 增加Logic
                $junctionLogic->save();

                // 操作logicEx表
                $junctionLogicEx->save();

                // 操作LogicMap表
                foreach ($junctionLogicMaps as $junctionMap) {
                    $junctionMap->save();
                }

                // 操作inoutLink， inoutLinkMap, flowMap 录入数据库
                foreach ($inoutLinkLogics as $inoutLinkLogic) {
                    $inoutLinkLogic->save();
                }

                foreach ($inoutLinkLogicMaps as $inoutLinkLogicMap) {
                    $inoutLinkLogicMap->save();
                }

                foreach ($flowLogicMaps as $flowLogicMap) {
                    $flowLogicMap->save();
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            OperationLog::logAdd(null, $package, OperationLog::ADDJUNCTION);

            return $junctionLogic->toArray();
        }
    }

    public function editBase($logicId, array $attr)
    {
        $junctionLogic = JunctionLogic::available()->where('logic_junction_id', $logicId)->first();
        $oldJunctionLogic = unserialize(serialize($junctionLogic));
        if (empty($junctionLogic)) {
            throw new JunctionNotExistException();
        }
        $junctionLogic->is_manual = 1;
        if (isset($attr['name']) && !empty($attr['name'])) {
            $junctionLogic->name_manual = $attr['name'];
        }
        $junctionLogic->save();

        OperationLog::logSaveBase($oldJunctionLogic, $junctionLogic, OperationLog::EDITEASYINFO);

        return $junctionLogic->toArrayWithMap();
    }

    // 这里的versionNodeIds必须有所有的版本，和add相同
    public function editVersionMaps($logicId, $versionNodeIds, $flagVersion)
    {
        $this->checkVersionNodeIds($versionNodeIds, $flagVersion);

        $junctionLogic = JunctionLogic::available()->where('logic_junction_id', $logicId)->first();
        if (empty($junctionLogic)) {
            throw new JunctionNotExistException();
        }

        // 如果是简单路口，且为非人工的，那么就需要重新生成一个路口
        if ($junctionLogic->is_manual == 0) {
            $newJunctionLogic = JunctionLogic::newJunction($flagVersion, $versionNodeIds[$flagVersion], $junctionLogic->city_id, ['name' => $junctionLogic->name]);

            $versionIds = array_keys($versionNodeIds);
            $newJunctionLogic->start_version = min($versionIds);
            $newJunctionLogic->end_version = Version::nextVersion(max($versionIds));

            $package = Package::generate($newJunctionLogic, $versionNodeIds, $flagVersion, $junctionLogic->logic_junction_id);
            $junctionLogicEx = $package->junctionLogicEx;
            $junctionLogicMaps = $package->junctionLogicMaps;
            $inoutLinkLogics = $package->inoutLinkLogics;
            $inoutLinkLogicMaps = $package->inoutLinkLogicMaps;
            $flowLogicMaps = $package->flowLogicMaps;

            DB::beginTransaction();

            try {

                $junctionLogic->is_deleted = 1;
                $junctionLogic->save();

                // 操作Logic表： 1 增加Logic
                $newJunctionLogic->save();

                // 操作logicEx表
                $junctionLogicEx->save();

                // 操作LogicMap表
                foreach ($junctionLogicMaps as $junctionMap) {
                    $junctionMap->save();
                }

                // 操作inoutLink， inoutLinkMap, flowMap 录入数据库
                foreach ($inoutLinkLogics as $inoutLinkLogic) {
                    $inoutLinkLogic->save();
                }

                foreach ($inoutLinkLogicMaps as $inoutLinkLogicMap) {
                    $inoutLinkLogicMap->save();
                }

                foreach ($flowLogicMaps as $flowLogicMap) {
                    $flowLogicMap->save();
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            OperationLog::logSaveSimpleMaps($junctionLogic, $package, OperationLog::EDITSIMPLEMAPS);

            return $newJunctionLogic->toArray();
        } else {

            // 如果已经是人工路口了, 修改下对应的版本映射的nodeIds

            // 删除这个JunctionId 对应的数据库中的数据
            $oldPackage = Package::getFromDB($junctionLogic);
            $newPackage = Package::generate($junctionLogic, $versionNodeIds, $flagVersion);

            $diff = $oldPackage->diff($newPackage);
            extract($diff);

            DB::beginTransaction();
            try {

                foreach ($toDel as $item) {
                    $item->delete();
                }

                foreach($toAdd as $item) {
                    $item->save();
                }

                foreach ($toUpdate as $item) {
                    $item->save();
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            OperationLog::logSaveComplexMaps($oldPackage, $newPackage, OperationLog::EDITCOMPLEXMAPS);

            return $junctionLogic->toArray();
        }
    }

    public function editFlagVersion($logicId, $flagVersion)
    {
        // 该操作不可用
        throw new ForbiddenOperation("编辑旗帜版本");
        /*
        $junctionLogic = JunctionLogic::available()->where('logic_junction_id', $logicId)->first();
        if (empty($junctionLogic)) {
            throw new JunctionNotExistException();
        }

        $logicEx = JunctionLogicEx::where('logic_junction_id', $logicId)->first();
        if (empty($logicEx)) {
            $logicEx = new JunctionLogicEx();
            $logicEx->logic_junction_id = $logicId;
        }
        $logicEx->version = $flagVersion;
        $logicEx->save();

        return $junctionLogic->toArrayWithMap();
        */
    }

    public function delete($logicId)
    {
        $junctionLogic = JunctionLogic::available()->where('logic_junction_id', $logicId)->first();
        if (empty($junctionLogic)) {
            throw new JunctionNotExistException();
        }
        $junctionLogic->is_deleted = 1;
        $junctionLogic->save();

        OperationLog::logDelete($junctionLogic, OperationLog::DELETEJUNCTION);
    }

    public function findByMainNodeId($mainNodeId, $version)
    {
        $junctions = JunctionLogicMap::where('simple_main_node_id', $mainNodeId)->where('start_version', "<=", $version)->where('end_version', '>', $version)->get();

        if (count($junctions) >= 2) {
            throw new MainNodeDuplicateExist();
        }

        if (count($junctions) == 0) {
            return [];
        }

        return $junctions->first()->toArray();
    }


    /*
     * 所有所有路口id的flagVersion
     */
    public function flagVersions($junctionIds)
    {
        $junctionLogics = JunctionLogic::available()->whereIn('logic_junction_id', $junctionIds)->get();
        $junctionLogics->load("logicEx");

        $ret = [];
        foreach ($junctionLogics as $junctionLogic) {
            $flagVersion = Version::preVersion($junctionLogic->end_version);
            if ($junctionLogic->logicEx) {
                $flagVersion = $junctionLogic->logicEx->version;
            }
            $ret[$junctionLogic->logic_junction_id] = [
                'is_manual' => $junctionLogic->is_manual,
                'default_flag_version' => $flagVersion,
            ];
        }
        return $ret;
    }

    /*
     * 获取一批路网id，在某个版本下的映射信息
     */
    public function maps($junctionIds, $version)
    {
        $junctionLogicMaps = JunctionLogicMap::whereIn('logic_junction_id', $junctionIds)->rangeVersion($version)->get();

        // 检查是否有两个路口在一个版本有两条数据
        $groupedJunctionLogicMaps = $junctionLogicMaps->groupBy('logic_junction_id');
        foreach ($groupedJunctionLogicMaps as $junctionId => $logicJunctionMaps) {
            if (count($logicJunctionMaps) != 1) {
                throw  new DuplicateMapInOneVesion($junctionId, $version);
            }
        }

        return $junctionLogicMaps->toArray();
    }

    /*
     * 添加或编辑路口映射关系时做检查：
     * 1. version_node_ids、flag_version均不能为空，且flag_version必须要在version_node_ids中存在
     * 2. version_node_ids里每个版本的nodeIds必须有相应node id，即不能为空
     */
    private function checkVersionNodeIds($versionNodeIds, $flagVersion)
    {
        if (empty($versionNodeIds)) {
            throw new VersionNodeIdsEmpty();
        }
        if (empty($flagVersion)) {
            throw new FlagVersionEmpty();
        }
        if (!isset($versionNodeIds[$flagVersion])) {
            throw new FlagVersionMustInVersionNodeIds();
        }
        foreach ($versionNodeIds as $version => $nodeIds ) {
            if (empty($nodeIds)) {
                throw new VersionHasNoNodeIds($version);
            }
        }
    }
}