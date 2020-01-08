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

    //南京自定义标题
    public function nanjing_title(){
        if($this->username == "13051117370"){
            $data = [
                'title'=>"远程交通信号优化配时中心",
                'style'=>1
            ];
        }else{
            $data = [
                'title'=>"南京城市大脑",
                'style'=>2
            ];
        }
        return $this->response($data);

    }

    //pi等级配置
    public function piconfig(){
        $data = [
            [
                'level'=>"E",
                "to"=>99999,
                "from"=>80
            ],
            [
                'level'=>"D",
                "to"=>80,
                "from"=>60
            ],
            [
                'level'=>"C",
                "to"=>60,
                "from"=>40
            ],
            [
                'level'=>"B",
                "to"=>40,
                "from"=>20
            ],
            [
                'level'=>"A",
                "to"=>20,
                "from"=>0
            ],

        ];
        return $this->response($data);
    }

    //南京项目需求,附加pi和行政区划
    public function junctionsListWithExt(){
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
//            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ]);

        $data = $this->overviewService->junctionsListWithPI($params,$this->userPerm);

//        if(isset($params['division_id']) && $params['division_id']>0){
        $data = $this->overviewService->addDivisionID($params['city_id'],$data);
//        }
        $this->response($data);
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

        //根据行政区,更改用户权限
        if(isset($params['division_id']) && $params['division_id']>0){
            $this->userPerm['group_id'] = 424;
        }

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

        //根据行政区划,更改权限 424
        if(isset($params['division_id']) && $params['division_id']>0){
            $this->userPerm['group_id'] = 424;
        }

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

        //根据行政区划,更改权限 424
        if(isset($params['division_id']) && $params['division_id']>0){
            $this->userPerm['group_id'] = 424;
        }

        $data = $this->overviewService->todayJamCurve($params,$this->userPerm);
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

        //针对行政区进行过滤,暂时只处理建邺区
        if(isset($params['division_id']) && $params['division_id']==320105){
            foreach ($data['res']['data'] as $d){
                if($d['obj_name'] == '建邺区'){
                    $data['res']['data'] = [$d];
                }
            }
        }


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
        //针对行政区进行过滤
        if(isset($params['division_id']) && $params['division_id']==320105){
            $reqData = [
                "cityname"=>"南京市",
                "grain"=>"minute",
                "obj_type"=>2,
                "pagenumber"=>1,
                "pagesize"=>10,
                "has_geo"=>0,
                "stime"=> date("Ymd")."0000",
                "etime"=> date("YmdHi")
            ];
            $data = httpPOST($url,$reqData,0,'json');
            $data = json_decode($data,true);
            if(empty($data['res'])){
                $this->response([]);
            }
            $data = $data['res'];
            foreach ($data as $d){
                if($d['obj_name'] == '建邺区'){
                    $data = [$d];
                }
            }
        }else{
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
            if(empty($data['res'])){
                $this->response([]);
            }
            $data = $data['res'];
        }


        $ttiInfo = [];
        foreach ($data[0]['tti_info'] as $ttk => $tti){
            $hour = substr($ttk,8,2);
            $min = substr($ttk,10,2);
            if($min != '00' && $min != '30'){
                continue;
            }
            $tti[0] = round($tti[0],2);
            $tti[1] = round($tti[1],2);
            $ttiInfo[$hour.":".$min] = $tti;
        }
        $data[0]['tti_info'] = $ttiInfo;
        //过滤数据,只保留整点数据
        $this->response($data);
    }


}
