<?php
/**
 * 路口分析报告模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\JunctionService;
use Services\JunctionReportService;

class JunctionReport extends MY_Controller
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

        $data = $this->junctionService->queryQuotaInfo($params);

        $this->response($data);
    }

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

        $data = $this->junctionReportService->introduction($params);
        $this->response($data);
    }

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

        $data = $this->junctionReportService->queryJuncDataComparison($params);
        $this->response($data);
    }

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
            "overview"=>"路口各个转向的运行指标(包括停车次数、停车延误、行驶速度、排队长度等指标)变化情况如下图所示。",
            "quotalist"=>$finalData,
            "junction_info"=>$junctionInfo
        ];
        $this->response($finalData);
    }


}