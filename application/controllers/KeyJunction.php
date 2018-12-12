<?php
/***************************************************************
# 干线绿波类
# user:ningxiangbing@didichuxing.com
# date:2018-06-29
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\OverviewService;

class KeyJunction extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->overviewService = new OverviewService();
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
            'junction_id' => 'required',
        ]);

        //默认数据从昨天开始
        $params['date'] = $params['date'] ?? date('Y-m-d',strtotime('-1 day'));
        
        $data = $this->overviewService->junctionStopDelayCurve($params);
        $this->response($data);
    }

    public function getJunctionTiming()
    {
        echo '{"errno":0,"errmsg":"","data":{"cycle":"200","offset":"161","timing":[{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"北左"},{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"北直"},{"state":"1","start_time":"0","duration":"35","end_time":35,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"西左"},{"state":"1","start_time":"35","duration":"83","end_time":118,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"东直"},{"state":"1","start_time":"35","duration":"83","end_time":118,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"东左"},{"state":"1","start_time":"118","duration":"82","end_time":200,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"南直"},{"state":"1","start_time":"118","duration":"54","end_time":172,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"南左"},{"state":"1","start_time":"50","duration":"53","end_time":103,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"西右"}]},"traceid":"546b8cd7777a4b27a3d28046abf2bb35","username":"unknown","time":{"a":"0.0868秒","s":"0.0837秒"}}';
        exit;
    }
}
