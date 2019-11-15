<?php
/***************************************************************
 * # 概览类
 * #    概览页---路口概况
 * #    概览页---路口列表
 * #    概览页---运行概况
 * #    概览页---拥堵概览
 * #    概览页---获取token
 * # user:ningxiangbing@didichuxing.com
 * # date:2018-07-25
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\OverviewService;

class Overview extends MY_Controller
{
    protected $overviewService;

    public function __construct()
    {
        parent::__construct();

        $this->overviewService = new OverviewService();
    }

    /**
     * 获取路口列表
     *
     * @throws Exception
     */
    public function junctionsList()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ]);

        $params['date'] = $params['date'] ?? date('Y-m-d');


        $data = $this->overviewService->junctionsList($params,$this->userPerm);
        


//        $data = json_decode($jsonStr,true);
//        $data = $data['data'];
        //新增基于行政区过滤功能
        if(isset($params['division_id']) && $params['division_id']>0){
            $data = $this->overviewService->addDivisionID($params['city_id'],$params['division_id'],$data);
        }

        $this->response($data);
    }

    /**
     * 运行情况 （概览页 平均延误）
     * @param $params['city_id'] int    Y 城市ID
     * @param $params['date']    string N 日期 yyyy-mm-dd
     * @throws Exception
     */
    public function operationCondition()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ]);

        $params['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->overviewService->operationCondition($params,$this->userPerm);

        $this->response($data);

    }

    /**
     * 路口概况
     *
     * @throws Exception
     */
    public function junctionSurvey()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ]);

        $params['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->overviewService->junctionSurvey($params,$this->userPerm);
        if($params['city_id'] == 12){   //济南项目,概览页路口总数暂时写死,与济南本地化项目(traj_service)数值保持一致
            $data['junction_total'] = 1589;
        }
        $this->response($data);

    }

    public function todayJamCurve()
    {
        $this->convertJsonToPost();
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id'    => 'required|is_natural_no_zero',
        ]);
        $data = $this->overviewService->todayJamCurve($params);
        $this->response($data);
    }

    /**
     * 拥堵概览
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 当前时间点 格式：H:i:s 例：09:10:00
     * @return json
     * @throws Exception
     */
    public function getCongestionInfo()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id'    => 'required|is_natural_no_zero',
            'date'       => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'time_point' => 'exact_length[8]|regex_match[/\d{2}:\d{2}:\d{2}/]',
        ]);

        $params['date']       = $params['date'] ?? date('Y-m-d');
        $params['time_point'] = $params['time_point'] ?? date('H:i:s');
        $result = $this->overviewService->getCongestionInfo($params,$this->userPerm);

        $this->response($result);
    }

    /**
     * 获取token
     */
    public function getToken()
    {
        $data = $this->overviewService->getToken();

        $this->response($data);
    }

    /**
     * 验证token
     *
     * @throws Exception
     */
    public function verifyToken()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'tokenval' => 'required|trim|min_length[1]',
        ]);

        $data = $this->overviewService->verifyToken($params);

        $this->response($data);
    }

    /**
     * 获取当前时间和日期
     */
    public function getNowDate()
    {
        $data = $this->overviewService->getNowDate();

        $this->response($data);
    }


    /*
     * 查询tti概览信息,通过大脑的开放平台
     * */
    public function ttioverview(){
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);
        if($params['city_id']!=11){
            throw new \Exception('暂时不支持此城市！', ERR_PARAMETERS);
        }
        $url = "http://sts.didichuxing.com/api/tti/info_by_citytype?token=8b6a3fa13729ff63b4e6f66e2981ee5a";
        $reqData = [
            "cityname"=>"南京市",
            "grain"=>"realtime",
            "obj_type"=>2,
            "pagenumber"=>1,
            "pagesize"=>10,
            "has_geo"=>0
        ];
        $data = httpPOST($url,$reqData,0,'json');
        $data = json_decode($data,true);
        $this->response($data['res']);
    }

    /*
     * 查询tti随时间变化数据
     * */
    public function getttiinfo(){
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);
        if($params['city_id']!=11){
            throw new \Exception('暂时不支持此城市！', ERR_PARAMETERS);
        }
        $url = "http://sts.didichuxing.com/api/tti/get_tti_info_by_citytype?token=8b6a3fa13729ff63b4e6f66e2981ee5a";
        $reqData = [
            "cityname"=>"南京市",
            "grain"=>"minute",
            "obj_type"=>1,
            "pagenumber"=>1,
            "pagesize"=>10,
            "has_geo"=>0,
            "stime"=> date("Ymd")."0000",
            "etime"=> date("YmdHi")
        ];
        $data = httpPOST($url,$reqData,0,'json');
        $data = json_decode($data,true);
        $this->response($data['res']);
    }


}
