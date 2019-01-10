<?php
/***************************************************************
# 干线绿波类
# user:ningxiangbing@didichuxing.com
# date:2018-06-29
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\OverviewService;
use Services\EvaluateService;


class KeyJunction extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->overviewService = new OverviewService();
        $this->evaluateService = new EvaluateService();
    }

    /**
     * 获取延误TOP20
     *
     * @throws Exception
     */
    public function stopDelayTopList()
    {
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'pagesize' => 'is_natural_no_zero'
        ]);
        $params['date'] = $params['date'] ?? date('Y-m-d');
        $params['pagesize'] = $params['pagesize'] ?? 20;
        //获取重点路口数据
        $keyJunctionList  = $this->config->item('key_junction_list');
        $params['junction_ids'] = !empty($keyJunctionList[$params['city_id']]) ? $keyJunctionList[$params['city_id']] : [];
        $params['junction_ids'] = implode($params['junction_ids'],",");

        if(empty($params['junction_ids'])){
            $this->errno = -1;
            $this->errmsg = 'key_junction_ids empty.';
            return;
        }
        $data = $this->overviewService->stopDelayTopList($params);
        $this->response($data);
    }
    
    public function stopDelayCurve(){
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ]);

        //获取重点路口数据
        $keyJunctionList  = $this->config->item('timing_junction_list');
        $cityJunction = !empty($keyJunctionList[$params['city_id']]) ? $keyJunctionList[$params['city_id']] : [];

        $junctionList = [];
        foreach ($cityJunction as $junctionId => $junctionName) {
            //默认数据从昨天开始，往前推7天
            $dayLength = 6;
            $params['date'] = $params['date'] ?? date('Y-m-d',strtotime('-1 day'));
            $baseTimeStartEnd = [];
            $baseTimeStartEnd['start'] = date("Y-m-d 00:00:00", strtotime($params['date'])-$dayLength*24*3600);
            $baseTimeStartEnd['end'] = date("Y-m-d 23:59:59", strtotime($params['date']));
            $baseTime = [];
            $incTime = 0;
            while(1){
                if(($incTime)>=($dayLength+1)){
                    break;
                }
                $baseTime[] = strtotime($params['date'])-($dayLength-$incTime)*24*3600;
                $incTime++;
            }
            $requestData = [
                "city_id"=>$params['city_id'],
                "quota_key"=>"stop_delay",
                "junction_id"=>$junctionId,
                "flow_id"=>"9999",
                "base_time_start_end"=>$baseTimeStartEnd,
                "base_time"=>$baseTime,
                "evaluate_time"=>[],
                "evaluate_time_start_end"=>[],
            ];
            // print_r($requestData);exit;
            $response = $this->evaluateService->quotaEvaluateCompare($requestData);
            $junctionResult = [];
            $junctionResult["datalist"] = isset($response['base']) ? $response['base']:[];
            $junctionResult["info"] = [
                "value"=>"停车延误",
                "name"=>$junctionName,
                "unit"=>"秒",
            ];
            $junctionList[$junctionId] = $junctionResult;
        }
        $this->response($junctionList);
    }

    public function getJunctionTiming()
    {
        echo '{"errno":0,"errmsg":"","data":{"2017030116_1341259":{"cycle":"200","offset":"161","timing":[{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"北左"},{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"北直"},{"state":"1","start_time":"0","duration":"35","end_time":35,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"西左"},{"state":"1","start_time":"35","duration":"83","end_time":118,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"东直"},{"state":"1","start_time":"35","duration":"83","end_time":118,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"东左"},{"state":"1","start_time":"118","duration":"82","end_time":200,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"南直"},{"state":"1","start_time":"118","duration":"54","end_time":172,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"南左"},{"state":"1","start_time":"50","duration":"53","end_time":103,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"西右"}]}},"traceid":"546b8cd7777a4b27a3d28046abf2bb35","username":"unknown","time":{"a":"0.0868秒","s":"0.0837秒"}}';
        exit;
    }
}
