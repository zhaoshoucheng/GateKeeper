<?php
/***************************************************************
# 公共方法类
# 1、获取路口所属行政区域及交叉节点信息
# user:ningxiangbing@didichuxing.com
# date:2018-08-23
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\CommonService;
use Didi\Cloud\Collection\Collection;
use Services\ParametermanageService;

/**
 * Class Common
 * @property \Waymap_model         $waymap_model
 */
class Common extends MY_Controller
{
    protected $commonService;
    protected $parametermanageService;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('waymap_model');
        $this->load->model('area_model');
        $this->load->model('redis_model');
        $this->load->model('realtimealarmconfig_model');
        $this->load->config('alarmanalysis_conf');
        $this->commonService = new commonService();
        $this->parametermanageService = new parametermanageService();
    }

    /**
     * 获取路口所属行政区域及交叉节点信息
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string Y 路口ID
     * @param $params['map_version']       string Y 地图版本
     * @return json
     */
    public function getJunctionAdAndCross()
    {
        $params = $this->input->post(null, true);

        // 校验参数
        $this->validate([
            'logic_junction_id' => 'required|trim|min_length[8]',
            'city_id'           => 'required|is_natural_no_zero',
        ]);

        $data['city_id'] = intval($params['city_id']);
        $data['logic_junction_id'] = strip_tags(trim($params['logic_junction_id']));

        if (!empty($params['map_version'])) {
            $data['map_version'] = $params['map_version'];
        }
        $result = $this->commonService->getJunctionAdAndCross($data);
        $this->response($result);
    }

    /**
     * 获取路口相位信息
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string Y 路口ID
     * @return json
     */
    public function getJunctionMovements()
    {
        $params = $this->input->post(null, true);

        // 校验参数
        $this->validate([
            'logic_junction_id' => 'required|trim|min_length[8]',
            'city_id'           => 'required|is_natural_no_zero',
        ]);

        $data['city_id'] = intval($params['city_id']);
        $data['logic_junction_id'] = strip_tags(trim($params['logic_junction_id']));

        $result['dataList'] = $this->commonService->getJunctionMovements($data);

        $this->response($result);
    }

    /**
     * 区域数据接口
     * 权限sso用，获取开城城市列表、行政区域、自定义区域、自定义干线、所有路口
     * @param $params['cityId']   long N 城市ID 默认传递
     * @param $params['areaId']   long N 城市ID areaType非零情况下必填，取自开城城市列表返回接口中的areaId
     * @param $params['areaType']  int  Y 0：开城城市列表，1：行政区域 ，2：自定义区域，3：干线，4：路口
     * @return json
     */
    public function areaData()
    {
        $params = $this->input->post(null, true);
        // 校验参数
        $this->validate([
            'areaType' => 'required|is_natural',
        ]);

        if (in_array($params['areaType'], [1, 2, 3, 4])) {
            if (intval($params['areaId']) < 1) {
                throw new \Exception('areaId不能为空！', ERR_PARAMETERS);
            }
        }

        switch ($params['areaType']) {
            case 1:
                // 根据城市ID获取所有行政区域
                $result = $this->commonService->getAllAdminAreaByCityId($params['areaId']);
                break;

            case 2:
                // 根据城市ID获取所有自定义区域
                $result = $this->commonService->getAllCustomAreaByCityId($params['areaId']);
                break;

            case 3:
                // 根据城市ID获取所有自定义干线
                $result = $this->commonService->getAllCustomRoadByCityId($params['areaId']);
                break;

            case 4:
                // 根据城市ID获取所有路口
                $result = $this->commonService->getAllJunctionByCityId($params['cityId'], $params['areaId']);
                break;
            default:
                // 获取开城城市列表
                $result = $this->commonService->getOpenCityList();
                break;
        }

        $this->response($result);
    }

    /**
     * 获取实时开城列表数据
     */
    public function getRealtimeV2CityIds(){
        $this->load->config('nconf');
        //$quotaCityIds = $this->config->item('quota_v2_city_ids');
        $quotaCityIds = $this->commonService->getV5DMPCityID();
        $this->response($quotaCityIds);
    }

    /**
     * 获取经纬度附近路口
     * @throws Exception
     */
    public function nearestJuncByCoordinate()
    {
        $cityId = $this->input->get("city_id",true);
        $lng = $this->input->get("lng",true);
        $lat = $this->input->get("lat",true);
        if(empty($cityId)||empty($lng)||empty($lat)){
            throw new \Exception('参数不能为空！', ERR_PARAMETERS);
        }
        $data = $this->waymap_model->nearestJuncByCoordinate($cityId,$lng,$lat);
        $this->response($data);
    }

    /**
     * 获取全城离线报警配置
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getCityAreaOfflineAlarmConfig()
    {
        $cityId = $this->input->get("city_id",true);
        $lastTime = $this->input->get("last_time",true);
        if(empty($cityId)){
            throw new \Exception('city_id不能为空！', ERR_PARAMETERS);
        }

        //获取区域列表
        $areaList  = $this->area_model->getAreasByCityId($cityId, 'id, area_name');
        $areaCollection = Collection::make($areaList);
        $areaIdList = $areaCollection->column('id')->get();
        $areaJunctionList = [];
        if(!empty($areaIdList)){
            $areaJunctionList = $this->area_model->getAreaJunctionsByAreaIds($areaIdList);
        }
        $junctionListKeyByAreaID = [];
        foreach ($areaJunctionList as $value) {
            $junctionListKeyByAreaID[$value["area_id"]][] = $value["junction_id"];
        }
        $newAreaList = [];
        $areaIdList[] = "-1";
        foreach ($areaIdList as $areaID){
            //获取某区域的配置
            $params = ["city_id"=>$cityId,"area_id"=>$areaID,"is_default"=>0,];
            $paramsList = $this->parametermanageService->paramList($params,true);
            if(empty($paramsList)){
                continue;
            }
            //整合区域和路口列表
            $paramList = $paramsList["params"]??[];
            $hourParamList = [];
            ksort($paramList);
            foreach ($paramList as $key => $value) {
                $nowTimestamp = strtotime(date("Y-m-d"));
                $hourTimestamp = $nowTimestamp+$key*3600;
                $hourstring = date("H:i",$hourTimestamp);
                $nexthourstring = date("H:i",$hourTimestamp+3600);
                $hourParamList[$hourstring."-".$nexthourstring] = json_encode($value);
            }
            $junctionList = $junctionListKeyByAreaID[$areaID] ?? [];
            $newAreaList[$areaID]["junction_list"] = $junctionList;
            $newAreaList[$areaID]["config"] = $hourParamList;
        }

        $newAreaList["default"]["junction_list"] = []; 
        $newAreaList["default"] = $newAreaList["-1"]??[];
        if(empty($newAreaList["-1"])){
            $defaultConfig = $this->config->item('alarm_param_offline_default');
            $newAreaList["default"]["config"]["00:00-24:00"] = $defaultConfig;
        }
        $this->response(["data"=>$newAreaList,"last_time"=>date("Y-m-d H:i:s")]);
    }

    public function getCityAreaRealtimeAlarmConfig()
    {
        $cityId = $this->input->get("city_id",true);
        $lastTime = $this->input->get("last_time",true);
        if(empty($cityId)){
            throw new \Exception('city_id不能为空！', ERR_PARAMETERS);
        }

        //获取区域列表
        $areaList  = $this->area_model->getAreasByCityId($cityId, 'id, area_name');
        $areaCollection = Collection::make($areaList);
        $areaIdList = $areaCollection->column('id')->get();
        $areaJunctionList = [];
        if(!empty($areaIdList)){
            $areaJunctionList = $this->area_model->getAreaJunctionsByAreaIds($areaIdList);
        }
        $junctionListKeyByAreaID = [];
        foreach ($areaJunctionList as $value) {
            $junctionListKeyByAreaID[$value["area_id"]][] = $value["junction_id"];
        }
        $newAreaList = [];
        $areaIdList[] = "-1";
        foreach ($areaIdList as $areaID){
            //获取某区域的配置
            $params = ["city_id"=>$cityId,"area_id"=>$areaID,"is_default"=>0,];
            $paramsList = $this->parametermanageService->realtimeAlarmParamList($params,true);
            if(empty($paramsList)){
                continue;
            }
            //整合区域和路口列表
            $paramList = $paramsList["params"]??[];
            $hourParamList = [];
            ksort($paramList);
            foreach ($paramList as $key => $value) {
                $nowTimestamp = strtotime(date("Y-m-d"));
                $hourTimestamp = $nowTimestamp+$key*3600;
                $hourstring = date("H:i",$hourTimestamp);
                $nexthourstring = date("H:i",$hourTimestamp+3600);
                $hourParamList[$hourstring."-".$nexthourstring] = json_encode($value);
            }
            $junctionList = $junctionListKeyByAreaID[$areaID] ?? [];
            $newAreaList[$areaID]["junction_list"] = $junctionList;
            $newAreaList[$areaID]["config"] = $hourParamList;
        }
        $newAreaList["default"]["junction_list"] = [];


        //这里读取-1区域数据，如果没有则使用0-24数据
        $newAreaList["default"] = $newAreaList["-1"]??[];
        if(empty($newAreaList["-1"])){
            $defaultConfig = $this->config->item('alarm_param_realtime_default');
        $newAreaList["default"]["config"]["00:00-24:00"] = $defaultConfig;
        }
        $this->response(["data"=>$newAreaList,"last_time"=>date("Y-m-d H:i:s")]);
    }
    
    public function getJunctionInPolygon(){
        $params = $this->input->post(null, true);
        // 校验参数
        $this->validate([
            'coords' => 'required|trim|min_length[8]',
        ]);
//        $params["coords"]='116.994696,36.653685;116.992035,36.668281;117.020788,36.680328;117.051258,36.683632;117.042761,36.657885';
        $result = $this->commonService->getJunctionInPolygon($params["city_id"],$params["coords"]);

        $this->response(["list"=>$result]);
    }


}
