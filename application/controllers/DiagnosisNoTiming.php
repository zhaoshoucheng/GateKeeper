<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Services\DiagnosisNoTimingService;

class DiagnosisNoTiming extends MY_Controller
{
    protected $dianosisService;

    public function __construct()
    {
        parent::__construct();

        $this->dianosisService = new DiagnosisNoTimingService();
        $this->load->model('junction_model');
        $this->load->model('timing_model');
        $this->load->config('nconf');
        $this->setTimingType();
    }

    /**
     * 获取路口指标详情
     * @param $params ['dates']           array    Y 评估/诊断日期 [20180102,20180103,....]
     * @param $params ['junction_id']     string   Y 逻辑路口ID
     * @param $params ['time_range']      string   N 方案的开始结束时间 (07:00-09:15) 时间点时传半小时间隔
     * @return json
     */
    public function getJunctionQuotaDetail()
    {
        $this->convertJsonToPost();
        
        // 校验参数
        $this->validate([
            'time_range' => 'required|trim|regex_match[/\d{2}:\d{2}-\d{2}:\d{2}/]',
            'city_id' => 'required|min_length[1]',
            'junction_id' => 'required|min_length[4]',
            'dates' => 'is_array',
        ], [
            'dates' => array(
                'is_array' => '%s 必须是一个数组',
            ),
        ]);
        $params = [];
        $params["time_range"] = $this->input->post("time_range", true);
        $params["city_id"] = $this->input->post("city_id", true);
        $params["junction_id"] = $this->input->post("junction_id", true);
        $params["dates"] = $this->input->post("dates", true);
        $params["time_range"] = $this->correntTimeRange($params["time_range"]);
        if(empty($params["dates"])){
            throw new \Exception("datas 不能为空");
        }
        foreach ($params["dates"] as $key=>$date){
            if (!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims',$date)){
                throw new \Exception("datas参数格式错误");
            }
        }
        $params["data_type"] = $this->input->post("data_type", true);
        $tmpDates = $params["dates"];
        if ($params["data_type"] == 1) {
            $params["dates"] = [];
            foreach ($tmpDates as $date) {
                $w = date('w', strtotime($date));
                if ($w >= 1 && $w <= 5) {
                    $params["dates"][] = $date;
                }
            } 
        } else if ($params["data_type"] == 2) {
            $params["dates"] = [];
            foreach ($tmpDates as $date) {
                $w = date('w', strtotime($date));
                if ($w < 1 || $w > 5) {
                    $params["dates"][] = $date;
                }
            }
        } else {
            $params["dates"] = $tmpDates;
        }

        // 获取路口指标详情
        $res = $this->dianosisService->getFlowQuotas($params);
        $this->response($res);
    }

    //纠正时间范围
    private function correntTimeRange($timeRange){
        $timeArr = explode("-",$timeRange);
        if($timeArr[0]==$timeArr[1]){
            $timeArr[1] = date("H:i",strtotime(date("Y-m-d")." ".$timeArr[1].":00")+30*60);
            return $timeArr[0]."-".$timeArr[1];
        }
        return $timeRange;
    }

    public function getJunctionQuestionTrend()
    {
        $this->convertJsonToPost();

        // 校验参数
        $this->validate([
            'city_id' => 'required|min_length[1]',
            'junction_id' => 'required|min_length[4]',
            'dates' => 'is_array',
        ], [
            'dates' => array(
                'is_array' => '%s 必须是一个数组',
            ),
        ]);
        $params = [];
        $params["city_id"] = $this->input->post("city_id", true);
        $params["junction_id"] = $this->input->post("junction_id", true);
        $params["dates"] = $this->input->post("dates", true);
        if(empty($params["dates"])){
            throw new \Exception("datas 不能为空");
        }
        foreach ($params["dates"] as $key=>$date){
            if (!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims',$date)){
                throw new \Exception("datas参数格式错误");
            }
        }

        // 获取路口指标详情
        $res = $this->dianosisService->getJunctionQuotaTrend($params);
        $this->response($res);
    }

    public function getJunctionMapData()
    {
        $this->convertJsonToPost();
        // 校验参数
        $this->validate([
            'city_id' => 'required|min_length[1]',
            'junction_id' => 'required|min_length[4]',
        ]);

        $data = [];
        $data['city_id'] = $this->input->post("city_id", TRUE);
        $data['junction_id'] = $this->input->post("junction_id", TRUE);
        $result = $this->dianosisService->getJunctionMapData($data,1);
        $this->response($result);
    }


    public function getSpaceTimeDiagram()
    {
        $this->convertJsonToPost();
        // 校验参数
        $this->validate([
            'city_id' => 'required|min_length[1]',
            'flow_id' => 'required|min_length[1]',
            'time_range' => 'required|min_length[1]',
            'junction_id' => 'required|min_length[4]',
            'dates' => 'is_array',
        ], [
            'dates' => array(
                'is_array' => '%s 必须是一个数组',
            ),
        ]);
        $params = [
            'city_id' => $this->input->post("city_id", TRUE),
            'flow_id' => $this->input->post("flow_id", TRUE),
            'time_range' => $this->input->post("time_range", TRUE),
            'junction_id' => $this->input->post("junction_id", TRUE),
            'dates' => $this->input->post("dates", TRUE),
        ];
        $params["time_range"] = $this->correntTimeRange($params["time_range"]);
        if(empty($params["dates"])){
            throw new \Exception("dates 不能为空");
        }
        foreach ($params["dates"] as $key=>$date){
            if (!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims',$date)){
                throw new \Exception("datas参数格式错误");
            }
        }
        $result_data = $this->dianosisService->getSpaceTimeDiagram($params);
        return $this->response($result_data);
    }

    public function getScatterDiagram()
    {
        $this->convertJsonToPost();
        // 校验参数
        $this->validate([
            'city_id' => 'required|min_length[1]',
            'flow_id' => 'required|min_length[1]',
            'time_range' => 'required|min_length[1]',
            'junction_id' => 'required|min_length[4]',
            'dates' => 'is_array',
        ], [
            'dates' => array(
                'is_array' => '%s 必须是一个数组',
            ),
        ]);
        $params = [
            'city_id' => $this->input->post("city_id", TRUE),
            'flow_id' => $this->input->post("flow_id", TRUE),
            'time_range' => $this->input->post("time_range", TRUE),
            'junction_id' => $this->input->post("junction_id", TRUE),
            'dates' => $this->input->post("dates", TRUE),
        ];
        $params["time_range"] = $this->correntTimeRange($params["time_range"]);
        if(empty($params["dates"])){
            throw new \Exception("datas 不能为空");
        }
        foreach ($params["dates"] as $key=>$date){
            if (!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims',$date)){
                throw new \Exception("datas参数格式错误");
            }
        }
        $result_data = $this->dianosisService->getScatterDiagram($params);
        return $this->response($result_data);
    }

    /**
     * 获取诊断路口问题趋势
     * @param $params ['city_id']       string   Y 城市ID
     * @param $params ['dates']         array    Y 评估/诊断日期 [20180102,20180103,....]
     * @return json
     */
    public function getQuestionTrend()
    {
        $this->convertJsonToPost();

        // 校验参数
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'dates' => 'is_array',
        ]);
        $params = [];
        $params["city_id"] = intval($this->input->post("city_id", true));
        $params["dates"] = $this->input->post("dates", true);
        if(empty($params["dates"])){
            throw new \Exception("datas 不能为空");
        }
        foreach ($params["dates"] as $key=>$date){
            if (!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims',$date)){
                throw new \Exception("datas参数格式错误");
            }
        }
        $res = $this->dianosisService->getJunctionAlarmDataByHour($params, $this->userPerm);
        $this->response($res);
    }

    /**
     * 诊断路口问题列表
     * @param $params ['city_id']       string   Y 城市ID
     * @param $params ['dates']         array    Y 评估/诊断日期 [20180102,20180103,....]
     * @param $params ['hour']          string   Y 评估/诊断日期 [09:30]
     * @return json
     */
    public function getAllCityJunctionsDiagnoseList()
    {
        $this->convertJsonToPost();

        // 校验参数
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'dates' => 'is_array',
            'hour' => 'required|trim|regex_match[/\d{1,2}:\d{2}/]',
        ]);
        $params = [];
        $params["city_id"] = intval($this->input->post("city_id", true));
        $params["dates"] = $this->input->post("dates", true);
        $params["hour"] = $this->input->post("hour", true);
        if (strlen($params["hour"]) == 4) {
            $params["hour"] = '0' . $params["hour"];
        }
        if(empty($params["dates"])){
            throw new \Exception("datas 不能为空");
        }
        foreach ($params["dates"] as $key=>$date){
            if (!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims',$date)){
                throw new \Exception("datas参数格式错误");
            }
        }

        $commonService = new \Services\CommonService();
        $cityUserPerm = $commonService->mergeUserPermAreaJunction($params['city_id'], $this->userPerm);

        $res = $this->dianosisService->getAllCityJunctionsDiagnoseList($params, $cityUserPerm);
        $this->response($res);
    }

    public function GetLastAlarmDateByCityID(){
        $params = [];
        $params["city_id"] = intval($this->input->get("city_id", true));
        if(empty($params["city_id"])){
            throw new \Exception("city_id为空");
        }
        $dt = $this->dianosisService->GetLastAlarmDateByCityID($params["city_id"]);
        $this->response($dt);
    }

    /**
     * 获取实时指标数据10分钟间隔
     * for 中控SaaS
     */
    public function GetQuotaFlowData(){
        $this->convertJsonToPost();
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required',
            'date' => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'hour' => 'required|trim|regex_match[/\d{1,2}:\d{2}/]',
        ]);
        $params = [];
        $params["city_id"] = intval($this->input->post("city_id", true));
        $params["date"] = $this->input->post("date", true);
        $params["logic_junction_id"] = $this->input->post("logic_junction_id", true);
        $params["hour"] = $this->input->post("hour", true);
        
        $bList = $this->dianosisService->GetBaseQuotaFlowData($params);
        $dList = $this->dianosisService->GetDiagnosisFlowData($params);
        $dMap = [];
        foreach($dList as $key=>$value){
            $dMap[$value["logic_flow_id"]] = $value;
        }
        foreach($bList as $key=>$value){
            $dInfo = $dMap[$value["logic_flow_id"]] ?? [];
            $bList[$key]["is_empty"] = $dInfo["is_empty"] ?? 0;
            $bList[$key]["is_oversaturation"] = $dInfo["is_oversaturation"] ?? 0;
            $bList[$key]["is_spillover"] = $dInfo["is_spillover"] ?? 0;
        }
        $this->response(["list"=>$bList,]);
    }
}
