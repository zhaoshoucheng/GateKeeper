<?php

namespace Services;

/**
 * Class RealtimeQuotaService
 *
 * @property \Realtime_model $realtime_model
 * @property \waymap_model $waymap_model
 * @property \Common_model $common_model
 * @package Services
 */
class RealtimeQuotaService extends BaseService
{
    private $helperService;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('realtime_model');
        $this->load->model('waymap_model');
        $this->load->helper('http_helper');
        $this->load->model('common_model');
        $this->helperService = new HelperService();
    }

    /**
     * 获取路口明细数据
     * @param $cityId
     * @param array $junctionIds
     * @param array $quotaKeys
     * @param array $userPerm
     * @return array
     */
    public function getFlowQuota($cityId, $inputJunctionIds = [], $quotaKeys = [],$userPerm=[])
    {
        //权限验证
        if(!empty($userPerm)){
            $cityIds = !empty($userPerm['city_id']) ? $userPerm['city_id'] : [];
            $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
            if(in_array($cityId,$cityIds)){
                $junctionIds = [];
            }
            if(!in_array($cityId,$cityIds) && empty($junctionIds)){ //无任何数据权限
                return [];
            }
            foreach ($inputJunctionIds as $jid){
                if(!empty($junctionIds) && !in_array($jid,$junctionIds)){
                    throw new \Exception("you don't have junction:$jid right.");
                }
            }
        }

        $date = date('Y-m-d');
        $hour = $this->helperService->getLastestHour($cityId);
        $flowList = $this->realtime_model->getRealTimeJunctionsQuota($cityId, $date, $hour, $inputJunctionIds);
        $flowInfo = $this->waymap_model->getFlowsInfo(implode(",", $inputJunctionIds));


        $newFlowList = [];
        foreach ($flowList as $key => $value) {
            $phaseName = $flowInfo[$value["junctionId"]][$value["movementId"]];
            foreach ($value as $vk => $vv) {
                $uncamelKey = uncamelize($vk);
                if (in_array($uncamelKey, $quotaKeys)) {
                    $newFlowList[$key][$uncamelKey] = $vv;
                }
            }

            $value["trailNum"] = $value["trailNum"]??0;
            switch ($value["trailNum"]){
                case ($value["trailNum"]>=30):
                    $newFlowList[$key]["confidence"] = "高";
                    break;
                case ($value["trailNum"]<=10):
                    $newFlowList[$key]["confidence"] = "低";
                    break;
                default:
                    $newFlowList[$key]["confidence"] = "中";
            }
            $newFlowList[$key]["phase_name"] = $phaseName;
            $newFlowList[$key]["logic_flow_id"] = $value["movementId"];
        }

        $junctionMovements = [];
        foreach ($inputJunctionIds as $junctionId){
            $junctionMovements[$junctionId] = $this->common_model->getTimingMovementNames($junctionId);
        }

        foreach ($newFlowList as $key => $item) {
            if (!empty($junctionMovements[$junctionId][$item["logic_flow_id"]])) {
                $newFlowList[$key]["movement_name"] = $junctionMovements[$junctionId][$item["logic_flow_id"]];
            }else{
                $newFlowList[$key]["movement_name"] = $newFlowList[$key]["phase_name"];
            }
        }
        return ["list"=>$newFlowList,"batch_time"=>$date." ".$hour];
    }
}