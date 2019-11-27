<?php
namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class AlarmWorksheetService
 * @package Services
 * @property \Alarmworksheet_model $alarmworksheet_model
 */
class AlarmWorksheetService extends BaseService
{
    /**
     * AlarmWorksheetService constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('alarmworksheet_model');
        $this->load->model('redis_model');
        $this->load->model('user/user', 'user');
        $this->load->config('nconf');
    }

    /**
     * 提交工单
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function submit($params)
    {
        $ext = [
            'create_time' => date("Y-m-d H:i:s"),
            'update_time' => date("Y-m-d H:i:s"),
        ];
        $params = array_merge($ext,$params);
        $sheetID = $this->alarmworksheet_model->insert($params);
        return $sheetID;
    }

    /**
     * 获取列表
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function getList($params)
    {
        $ret = $this->alarmworksheet_model->pageList($params);
        foreach ($ret["list"] as $key => $value) {
            $ret["list"][$key]["v_status"] = "1";    
            if($value["deal_valuation"]==""){
                $ret["list"][$key]["v_status"] = "0";
            }
            if($value["status"]=="1" && time()>strtotime($value["deadline_time"])){
                $ret["list"][$key]["status"] = "2";
            }
            if($value["status"]=="1"){
                $ret["list"][$key]["deal_time"] = "N/A";
            }
        }
        return $ret;
    }

    public function find($params)
    {
        return $this->alarmworksheet_model->find($params);
    }

    public function deal($params)
    {
        $ext = [
            'deal_user' => $this->username,
            'status' => 5,
            'update_time' => date("Y-m-d H:i:s"),
        ]; 
        $params = array_merge($ext,$params);
        $sheetID = $this->alarmworksheet_model->update($params);
        return $sheetID;
    }

    public function valuation($params)
    {
        $ext = [ 
            'update_time' => date("Y-m-d H:i:s"),
        ];
        $params = array_merge($ext,$params);
        $sheetID = $this->alarmworksheet_model->update($params);
        return $sheetID;
    }
    
    // /**
    //  * 获取城市全部区域详情
    //  * @param $params['city_id'] int 城市ID
    //  * @return array
    //  * @throws \Exception
    //  */
    // public function getCityAreaDetail($params)
    // {
    //     $cityId = $params['city_id'];

    //     // 获取城市全部区域信息
    //     $areaList       = $this->area_model->getAreasByCityId($cityId, 'id, area_name');
    //     if (empty($areaList)) {
    //         return (object)[];
    //     }
    //     $areaCollection = Collection::make($areaList);

    //     // 获取区域ID
    //     $areaIdList = $areaCollection->column('id')->get();

    //     // 获取 ID 和 名称的映射
    //     $areaIdToNameList = $areaCollection->column('area_name', 'id')->get();

    //     // 获取全部区域路口映射
    //     $areaJunctionList       = $this->area_model->getAreaJunctionsByAreaIds($areaIdList);
    //     $areaJunctionCollection = Collection::make($areaJunctionList);

    //     // 从路网获取路口信息
    //     $junctionIds    = $areaJunctionCollection->implode('junction_id', ',');
    //     $junctionList   = $this->waymap_model->getJunctionInfo($junctionIds);
    //     $junctionIdList = array_column($junctionList, null, 'logic_junction_id');

    //     $areaIdJunctionList = $areaJunctionCollection
    //         ->groupBy('area_id', function ($item) {
    //             return array_column($item, 'junction_id');
    //         })->krsort();

    //     $results = [];

    //     foreach ($areaIdJunctionList as $areaId => $junctionIds) {

    //         $junctionCollection = Collection::make([]);

    //         foreach ($junctionIds as $id) {
    //             $junctionCollection[] = $junctionIdList[$id] ?? '';
    //         }

    //         $results[] = [
    //             'area_id' => $areaId,
    //             'area_name' => $areaIdToNameList[$areaId] ?? '',
    //             'center_lat' => $junctionCollection->avg('lat'),
    //             'center_lng' => $junctionCollection->avg('lng'),
    //             'junction_list' => $junctionCollection,
    //         ];
    //     }

    //     return $results;
    // }
}