<?php
/**
 * 报告相关模块
 */

use Services\ReportService;

class Report extends MY_Controller
{
    protected $reportService;

    public function __construct()
    {
        parent::__construct();

        $this->reportService = new ReportService();
    }

    /**
     *
     */
    public function test()
    {
        $data = $this->reportService->test();

        $this->response($data);
    }



    public function autoReport()
    {
        $params = $this->input->post(null, true);

        //类型 日报,周报,月报   0,1,2
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'datetype' => 'required',
        ]);
        //相关区域干线暂时写死

        $roadIDs = ["a9ff0f8c6fabc79777e5426b80f118b7", "0bc6f81fd483b79f4b499581bee91672", "775df757eb84ad1109753b7adf78b750", "374a355a4948e7d3a5e0a92668275617", "69bdf91ec8d467d3ee4159922d09a5b6"];

        $areaIDs = [161];

        $raname =  $this->reportService->queryRoadAndAreaName($params['city_id'],$roadIDs,$areaIDs);
        $tasks=[];

        $date = "";
        if($params['datetype']==0){
            $date = date('Y-m-d',strtotime("-1 day"));
        }elseif ($params['datetype']==1){
            $date = date('Y-m-d',strtotime("last Monday"))."~".date('Y-m-d',strtotime("last Sunday"));
        }else{
            $last= strtotime("-1 month", time());
            $last_lastday = date("Y-m-t", $last);//上个月最后一天
            $last_firstday = date('Y-m-01', $last);//上个月第一天
            $date =$last_firstday."~".$last_lastday;
        }

        foreach ($roadIDs as $r){
            $title=$raname['raod_map'][$r];
            $task = [
                'city_id'=>$params['city_id'],
                'type'=>11,
                'title'=>$title,
                'time_range'=>$date,
                'url'=>sprintf("type=11&dateType=%s&date=%s&indicator=runningStateComparison,runningIndicators,coordinate,trafficAnalysis,alarmSummary,indicatorsRanking,indicatorsAnalysis&id=%s&title=%s&system=1&city_id=%s&focusList=",$params['datetype'],$date,$r,$title,$params['city_id'])
            ];
            $tasks[] = $task;
        }

        foreach ($areaIDs as $a){
            $title=$raname['area_map'][$a];
            $task = [
                'city_id'=>$params['city_id'],
                'type'=>12,
                'title'=>$title,
                'time_range'=>$date,
                'url'=>sprintf("type=12&dateType=%s&date=%s&indicator=runningStateComparison,runningIndicators,trajectory,trafficAnalysis,alarmSummary,indicatorsRanking,indicatorsAnalysis&id=%s&title=%s&system=1&city_id=%s&focusList=",$params['datetype'],$date,$a,$title,$params['city_id'])
            ];
            $tasks[] = $task;
        }
        $this->response($tasks);

    }


//        https://sts.didichuxing.com/nanjing/signalpro/report/preview?type=10&dateType=0&date=2019-11-19&indicator=runningStateComparison,runningIndicators,indicatorsAnalysis&id=2017030116_72600921&title=%E7%BB%8F%E5%85%AB%E8%B7%AF&system=1&city_id=11&focusList=
//        city_id: 11
//title: 经八路
//type: 10
//time_range: 2019-11-19
//    }

    /**
     * @throws Exception
     */
    public function searchJunction()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'keyword' => 'required|trim|min_length[1]',
        ]);

        $data = $this->reportService->searchJunction($params);

        $this->response($data);
    }

    public function searchRoad() {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'keyword' => 'required|trim|min_length[1]',
        ]);

        $data = $this->reportService->searchRoad($params);

        $this->response($data);
    }

    public function searchArea() {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'keyword' => 'required|trim|min_length[1]',
        ]);

        $data = $this->reportService->searchArea($params);

        $this->response($data);
    }

    /**
     * @throws Exception
     */
    public function reportConfig()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'type' => 'required|in_list[1,2,3,4,10,11,12]',
        ]);

        $data = $this->reportService->reportConfig($params);

        $this->response($data);
    }

    /**
     * 自动生成报告
     * @param $params['city_id'] int    城市ID
     * @param $params['title']   string 报告标题
     * @param $params['type']    int    报告类型 1，路口分析报告；2，路口优化对比报告；3，城市分析报告（周报）；4，城市分析报告（月报）10,路口报告,11,干线报告,12,区域报告
     * @param $params['file']    binary 二进制文件
     * @param $params['timerange'] string 时间区间 : 2019-10-01~2019-10-10
     * @throws Exception
     */
    public function autogenerate(){
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'title' => 'required|trim|min_length[1]',
            'type' => 'required',
            'time_range'=>'required',
//            'report_gift'=>'required'
        ]);

//        $data = [
//            'city_id' => intval($params['city_id']),
//            'title'   => trim($params['title']),
//            'type'    => intval($params['type']),
//            'time_range'=>$params['timerange'],
//        ];
        $data = $this->reportService->autoGenerate($params);

        $this->response($data);
    }

    /**
     * 生成报告
     * @param $params['city_id'] int    城市ID
     * @param $params['title']   string 报告标题
     * @param $params['type']    int    报告类型 1，路口分析报告；2，路口优化对比报告；3，城市分析报告（周报）；4，城市分析报告（月报）10,路口报告,11,干线报告,12,区域报告
     * @param $params['file']    binary 二进制文件
     * @param $params['timerange'] string 时间区间 : 2019-10-01~2019-10-10
     * @throws Exception
     */
    public function generate()
    {
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'title' => 'required|trim|min_length[1]',
            'type' => 'required',
        ]);

        $data = [
            'city_id' => intval($params['city_id']),
            'title'   => trim($params['title']),
            'type'    => intval($params['type']),
            'time_range'=>$params['timerange'],
        ];
        $data = $this->reportService->generate($data);

        $this->response($data);
    }

    /**
     * @throws Exception
     */
    public function getReportList()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'type' => 'required',
            'page_no' => 'required',
            'page_size' => 'required',
        ]);

        $data = $this->reportService->getReportList($params);

        $this->response($data);
    }

    public function reportProxy()
    {
        $params = $this->input->get(null, true);

        $opts = array('http' =>
            array(
                'method' => 'GET',
                'max_redirects' => '0',
                'ignore_errors' => '1'
            )
        );
        $url = base64_decode($params['url']);
        $context = stream_context_create($opts);
        $stream = fopen($url, 'r', false, $context);
        $body = stream_get_contents($stream);
        fclose($stream);
        foreach (get_headers($url) as $k=>$v){
            header($v);
        }

        echo $body;
        exit();

    }

    /**
     * @throws Exception
     */
    public function downReport()
    {
        $params = $this->input->get(null, true);
        if (empty($params['key'])) {
            throw new \Exception('key不能为空！', ERR_PARAMETERS);
        }

        $this->reportService->downReport($params);
    }
}