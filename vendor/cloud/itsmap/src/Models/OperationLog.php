<?php

namespace Didi\Cloud\ItsMap\Models;

use Didi\Cloud\ItsMap\User;

class OperationLog extends \Illuminate\Database\Eloquent\Model
{
    protected $table = "operation_log";

    // 增加路口
    const ADDJUNCTION = 1;

    // 删除路口
    const DELETEJUNCTION = 2;

    // 编辑路口简单信息
    const EDITEASYINFO = 3;

    // 编辑简单路口映射关系
    const EDITSIMPLEMAPS = 4;

    // 编辑复杂路口映射关系
    const EDITCOMPLEXMAPS = 5;

    public static function logAdd($oldPackage, $newPackage, $operationType)
    {
        try {
            $operationLog = new OperationLog();
            $operationLog->operation_type = $operationType;
            $operationLog->logic_junction_id = $newPackage->junctionLogic->logic_junction_id;

            $operationLog->package_before = json_encode($oldPackage);
            $operationLog->package_after = json_encode($newPackage);
            $operationLog->username = User::username();
            $operationLog->save();
        } catch (\Exception $e) {

        }
    }

    public static function logDelete($junctionLogic, $operationType)
    {
        try {
            $operationLog = new OperationLog();
            $operationLog->operation_type = $operationType;
            $operationLog->logic_junction_id = $junctionLogic->logic_junction_id;

            // 删除后最新的package信息，内含is_deleted = 1，由于toArray会忽略is_deleted字段，所以也相当于删除前的package信息
            $newPackage = Package::getFromDB($junctionLogic);

            // 删除只记录package_before
            $operationLog->package_before = json_encode($newPackage);
            $operationLog->username = User::username();
            $operationLog->save();
        } catch (\Exception $e) {

        }
    }

    public static function logSaveBase($oldJunctionLogic, $newJunctionLogic, $operationType)
    {
        try {
            $operationLog = new OperationLog();
            $operationLog->operation_type = $operationType;
            $operationLog->logic_junction_id = $newJunctionLogic->logic_junction_id;

            $newPackage = Package::getFromDB($newJunctionLogic);
            $oldPackage = unserialize(serialize($newPackage));
            $oldPackage->junctionLogic = $oldJunctionLogic;

            $operationLog->package_before = json_encode($oldPackage);
            $operationLog->package_after = json_encode($newPackage);
            $operationLog->username = User::username();
            $operationLog->save();
        } catch (\Exception $e) {

        }
    }

    private static function logSaveFlagVersion()
    {
        // 编辑旗帜版本操作不可用
    }

    public static function logSaveSimpleMaps($oldJunctionLogic, $newPackage, $operationType)
    {
        self::logDelete($oldJunctionLogic, $operationType);
        self::logAdd(null, $newPackage, $operationType);
    }

    public static function logSaveComplexMaps($oldPackage, $newPackage, $operationType)
    {
        try{
            $operationLog = new OperationLog();
            $operationLog->operation_type = $operationType;
            $operationLog->logic_junction_id = $newPackage->junctionLogic->logic_junction_id;

            $operationLog->package_before = json_encode($oldPackage);
            $operationLog->package_after = json_encode($newPackage);
            $operationLog->username = User::username();
            $operationLog->save();
        } catch (\Exception $e) {

        }
    }
}