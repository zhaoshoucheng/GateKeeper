<?php
/**
 * 路口分析报告模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\JunctionService;
use Services\JunctionReportService;

class JunctionReportJN extends MY_Controller
{
    protected $junctionService;
    protected $junctionReportService;

    public function __construct()
    {
        parent::__construct();

        $this->config->load('report_conf');

        $this->junctionService = new JunctionService();
        $this->junctionReportService = new JunctionReportService();
    }

    /*
     *  ================================== 济南定制化接口 start ==================================
     * */


    /*
     * 路口报告最终总结
     * */
    public function conclusion(){
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',

        ],$params);

        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];

        if(!isset($params['date_type'])){
            $params['date_type']=0;
        }

        $data1 = $this->junctionReportService->queryJunctionQuotaDataNJ($params);

//        $junctionInfo =$this->junctionReportService->queryJuncInfo($params['logic_junction_id']);

        $quotaData = $this->junctionReportService->queryJuncQuotaDetail($params['city_id'],$params['logic_junction_id'],$params['start_time'],$params['end_time']);
        //数据聚合
        $flowQuota=[];
        foreach ($quotaData['quota_info'] as $qk=>$qv){
            $flowQuota[$qk]=[];
            foreach ($qv as $yk=>$yv){
                foreach ($yv as $d){
                    if(!isset($flowQuota[$qk][$d['hour']])){
                        $flowQuota[$qk][$d['hour']]=[
                            'traj_count'=>0,
                            'speed'=>0,
                            'stop_delay'=>0,
                            'stop_time_cycle'=>0,
                        ];
                    }
                    $flowQuota[$qk][$d['hour']]['traj_count']+=$d['traj_count'];
                    $flowQuota[$qk][$d['hour']]['speed']+=$d['speed']*$d['traj_count']*3.6;
                    $flowQuota[$qk][$d['hour']]['stop_delay']+=$d['stop_delay']*$d['traj_count'];
                    $flowQuota[$qk][$d['hour']]['stop_time_cycle']+=$d['stop_time_cycle']*$d['traj_count'];
                }
            }
        }
        //数据转图表
        $chartData = $this->junctionReportService->trans2Chart($flowQuota,$quotaData['flow_info']);
//        $finalData = $this->junctionReportService->chartAnalysis($chartData);
        $conclusion = $this->junctionReportService->conclusionJN($chartData);
        $tpl= "通过交通大数据分析,%s。%s需重点关注存在问题的时段和方向,可以通过调整绿信比、相位差和周期的方式进行优化,从而缓解交通压力。";
        $this->response(sprintf($tpl,$data1['conclusion'],$conclusion));
    }


    /**
     * 济南定制化需求,路口报警总结
     *
     * @throws Exception
     */
    public function queryJuncAlarm(){
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',

        ],$params);

        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];

        if(!isset($params['date_type'])){
            $params['date_type']=0;
        }
        $data = $this->junctionReportService->queryJuncAlarm($params);
        $this->response($data);

    }

    /**
     * 济南定制化需求,查询上周和本周渠化图对比
     *
     * @throws Exception
     */
    public function queryJuncModel(){
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',

        ],$params);

        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];

        $data = $this->junctionReportService->queryJuncModelJN($params);

        $this->response($data);
    }

    /*
     * 对接当地接口
     * */
    public function queryVehicleCounting(){
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);

        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];

        if(!isset($params['date_type'])){
            $params['date_type']=0;
        }
//
//        echo "{\"errno\":0,\"errmsg\":\"\",\"data\":{\"scale_title\":\"\",\"series\":[{\"name\":\"\u672c\u5468\",\"data\":[{\"x\":\"00:00\",\"y\":5524},{\"x\":\"00:30\",\"y\":5094},{\"x\":\"01:00\",\"y\":5113},{\"x\":\"01:30\",\"y\":5488},{\"x\":\"02:00\",\"y\":5258},{\"x\":\"02:30\",\"y\":5440},{\"x\":\"03:00\",\"y\":5377},{\"x\":\"03:30\",\"y\":5495},{\"x\":\"04:00\",\"y\":5518},{\"x\":\"04:30\",\"y\":5037},{\"x\":\"05:00\",\"y\":5465},{\"x\":\"05:30\",\"y\":5514},{\"x\":\"06:00\",\"y\":5234},{\"x\":\"06:30\",\"y\":5707},{\"x\":\"07:00\",\"y\":5508},{\"x\":\"07:30\",\"y\":5519},{\"x\":\"08:00\",\"y\":5721},{\"x\":\"08:30\",\"y\":5351},{\"x\":\"09:00\",\"y\":5533},{\"x\":\"09:30\",\"y\":5484},{\"x\":\"10:00\",\"y\":5355},{\"x\":\"10:30\",\"y\":5609},{\"x\":\"11:00\",\"y\":5088},{\"x\":\"11:30\",\"y\":5369},{\"x\":\"12:00\",\"y\":5218},{\"x\":\"12:30\",\"y\":5525},{\"x\":\"13:00\",\"y\":5321},{\"x\":\"13:30\",\"y\":5443},{\"x\":\"14:00\",\"y\":5526},{\"x\":\"14:30\",\"y\":5873},{\"x\":\"15:00\",\"y\":5389},{\"x\":\"15:30\",\"y\":5562},{\"x\":\"16:00\",\"y\":5383},{\"x\":\"16:30\",\"y\":5278},{\"x\":\"17:00\",\"y\":5403},{\"x\":\"17:30\",\"y\":5289},{\"x\":\"18:00\",\"y\":5280},{\"x\":\"18:30\",\"y\":5429},{\"x\":\"19:00\",\"y\":5366},{\"x\":\"19:30\",\"y\":5502},{\"x\":\"20:00\",\"y\":5223},{\"x\":\"20:30\",\"y\":5714},{\"x\":\"21:00\",\"y\":5329},{\"x\":\"21:30\",\"y\":5433},{\"x\":\"22:00\",\"y\":5535},{\"x\":\"22:30\",\"y\":5744},{\"x\":\"23:00\",\"y\":5476}]},{\"name\":\"\u4e0a\u5468\",\"data\":[{\"x\":\"00:00\",\"y\":5376},{\"x\":\"00:30\",\"y\":5540},{\"x\":\"01:00\",\"y\":5306},{\"x\":\"01:30\",\"y\":5035},{\"x\":\"02:00\",\"y\":5027},{\"x\":\"02:30\",\"y\":5310},{\"x\":\"03:00\",\"y\":5180},{\"x\":\"03:30\",\"y\":5137},{\"x\":\"04:00\",\"y\":4838},{\"x\":\"04:30\",\"y\":5400},{\"x\":\"05:00\",\"y\":5490},{\"x\":\"05:30\",\"y\":5338},{\"x\":\"06:00\",\"y\":5541},{\"x\":\"06:30\",\"y\":5820},{\"x\":\"07:00\",\"y\":5624},{\"x\":\"07:30\",\"y\":5785},{\"x\":\"08:00\",\"y\":5579},{\"x\":\"08:30\",\"y\":5390},{\"x\":\"09:00\",\"y\":5435},{\"x\":\"09:30\",\"y\":5435},{\"x\":\"10:00\",\"y\":5555},{\"x\":\"10:30\",\"y\":5507},{\"x\":\"11:00\",\"y\":5454},{\"x\":\"11:30\",\"y\":5341},{\"x\":\"12:00\",\"y\":5104},{\"x\":\"12:30\",\"y\":4957},{\"x\":\"13:00\",\"y\":5553},{\"x\":\"13:30\",\"y\":5549},{\"x\":\"14:00\",\"y\":5223},{\"x\":\"14:30\",\"y\":5612},{\"x\":\"15:00\",\"y\":5135},{\"x\":\"15:30\",\"y\":5416},{\"x\":\"16:00\",\"y\":5253},{\"x\":\"16:30\",\"y\":5360},{\"x\":\"17:00\",\"y\":5332},{\"x\":\"17:30\",\"y\":5559},{\"x\":\"18:00\",\"y\":5043},{\"x\":\"18:30\",\"y\":5054},{\"x\":\"19:00\",\"y\":4916},{\"x\":\"19:30\",\"y\":5594},{\"x\":\"20:00\",\"y\":5063},{\"x\":\"20:30\",\"y\":5530},{\"x\":\"21:00\",\"y\":5522},{\"x\":\"21:30\",\"y\":5222},{\"x\":\"22:00\",\"y\":5557},{\"x\":\"22:30\",\"y\":5300},{\"x\":\"23:00\",\"y\":5274}]}],\"title\":\"\u8def\u53e3\u6d41\u91cf\",\"desc\":\"\u8def\u53e3\u5728\u5404\u4e2a\u65f6\u6bb5\u5185,\u8def\u53e3\u6574\u4f53\u4ea4\u901a\u6d41\u91cf\u53d8\u5316\u60c5\u51b5\u5982\u4e0b\u56fe\u6240\u793a\u3002\u672c\u5468\u6d41\u91cf\u6700\u5927\u7684\u65f6\u6bb5\u4e3a06:30-08:00,\u4e0e\u524d\u4e00\u5468\u76f8\u6bd4\u589e\u957f30.33%\"},\"traceid\":\"645add2c5d44627b6bb86d76295a7a02\",\"username\":\"02efffde3a9b5a8f8f04f7c00fb92cb0\",\"time\":{\"a\":\"4.3819\u79d2\",\"s\":\"1.9544\u79d2\"}}";
//        exit;
        $data = $this->junctionReportService->queryLocalFlowJN($params);

        $this->response($data);
    }


    /*
     * 概览
     * */
    public function introduction() {
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];
        if(!isset($params['date_type'])){
            $params['date_type']=0;
        }

        $data = $this->junctionReportService->introductionJN($params);

        $this->response($data);
    }

    /*
     * ================================== 济南定制化接口 end ==================================
     * */

    /**
     * 单点路口分析 - 数据获取
     *
     * @throws Exception
     */
    public function queryQuotaInfo()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|min_length[1]',
            'evaluate_start_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'evaluate_end_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'week[]' => 'required',
            'schedule_start' => 'required|exact_length[5]|regex_match[/\d{2}:\d{2}/]',
            'schedule_end' => 'required|exact_length[5]|regex_match[/\d{2}:\d{2}/]',
            'quota_key' => 'required|in_list[' . implode(',', array_keys($this->config->item('quotas'))) . ']',
            'type' => 'required|in_list[1,2]',
        ]);
        if(!isset($params['date_type'])){
            $params['date_type']=0;
        }

        $data = $this->junctionService->queryQuotaInfo($params);

        $this->response($data);
    }


    /*
     * 路口运行状态对比
     * */
    public function queryJuncDataComparison() {
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];
        if(!isset($params['date_type'])){
            $params['date_type']=0;
        }
//        if($this->userapp == 'jinanits'){ //济南新需求复用南京功能
        $data = $this->junctionReportService->queryJunctionDataComparisonNJ($params);
//        }else{
//            $data = $this->junctionReportService->queryJuncDataComparison($params);
//        }


        $this->response($data);
    }
    public function queryJuncDataComparisonNJ() {
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];
        if(!isset($params['date_type'])){
            $params['date_type']=0;
        }

        $data = $this->junctionReportService->queryJunctionDataComparisonNJ($params);
        $this->response($data);
    }
//    public function queryJuncQuotaDataNJ() {
//        $params = $this->input->get(null, true);
//        $this->get_validate([
//            'city_id' => 'required|is_natural_no_zero',
//            'logic_junction_id' => 'required|min_length[1]',
//            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
//            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
//        ],$params);
//        $params['start_date'] = $params['start_time'];
//        $params['end_date'] = $params['end_time'];
//        if(!isset($params['date_type'])){
//            $params['date_type']=0;
//        }
//
//        $data = $this->junctionReportService->queryJunctionQuotaDataNJ($params);
//        $this->response($data);
//    }

    /*
     * 路口运行情况
     * */
    public function queryJuncQuotaData() {
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];
        if(!isset($params['date_type'])){
            $params['date_type']=0;
        }

        $data = $this->junctionReportService->queryJuncQuotaData($params);


        $this->response($data);
    }

    /*路口运行指标分析*/
    public function queryJuncQuotaAnalysis(){
        $params = $this->input->get(null, true);

        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        if(!isset($params['date_type'])){
            $params['date_type']=0;
        }

        $junctionInfo =$this->junctionReportService->queryJuncInfo($params['logic_junction_id']);

        $quotaData = $this->junctionReportService->queryJuncQuotaDetail($params['city_id'],$params['logic_junction_id'],$params['start_time'],$params['end_time']);
        //数据聚合
        $flowQuota=[];
        foreach ($quotaData['quota_info'] as $qk=>$qv){
            $flowQuota[$qk]=[];
            foreach ($qv as $yk=>$yv){
                foreach ($yv as $d){
                    if(!isset($flowQuota[$qk][$d['hour']])){
                        $flowQuota[$qk][$d['hour']]=[
                            'traj_count'=>0,
                            'speed'=>0,
                            'stop_delay'=>0,
                            'stop_time_cycle'=>0,
                        ];
                    }
                    $flowQuota[$qk][$d['hour']]['traj_count']+=$d['traj_count'];
                    $flowQuota[$qk][$d['hour']]['speed']+=$d['speed']*$d['traj_count']*3.6;
                    $flowQuota[$qk][$d['hour']]['stop_delay']+=$d['stop_delay']*$d['traj_count'];
                    $flowQuota[$qk][$d['hour']]['stop_time_cycle']+=$d['stop_time_cycle']*$d['traj_count'];
                }
            }
        }
        //数据转图表
        $chartData = $this->junctionReportService->trans2Chart($flowQuota,$quotaData['flow_info']);
        $finalData = $this->junctionReportService->chartAnalysis($chartData);

        $junctionInfo['versionMaps']=[]; //减少无用数据的传输
        $finalData = [

            "overview"=>"路口各个转向的运行指标(包括停车次数、停车延误、行驶速度等指标)变化情况如下图所示。",
            "quotalist"=>$finalData,
            "junction_info"=>$junctionInfo
        ];
        $this->response($finalData);
    }


}