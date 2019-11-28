<?php
/***************************************************************
# 报警工单系统
***************************************************************/
defined('BASEPATH') OR exit('No direct script access allowed');

use Services\AlarmWorksheetService;
class AlarmWorksheet extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->alarmWorksheetService = new AlarmWorksheetService();
    }

    //提交问题 
    public function submit()
    {
        $this->convertJsonToPost();
        $params = $this->input->post(NULL,true);
        $this->validate([
            'city_id' => 'required|trim',
            'junction_id' => 'required|trim',
            'flow_id' => 'required|trim',
            'flow_alarm_type' => 'required|trim',
            'junction_alarm_type' => 'required|trim',
            'alarm_batch_num' => 'required|trim',
            'from_group' => 'required|trim',
            'from_user' => 'required|trim',
            'deadline_time' => 'required|trim',
            'problem_desc' => 'trim',
            'problem_pics' => 'trim',
            'problem_suggest' => 'trim',
            'to_group'=> 'required|trim',
        ]);
        $this->alarmWorksheetService->submit($params);
        return $this->response("");
    }

    //问题列表，（管理员、对应大队） 
    public function adminList() 
    {
        $this->convertJsonToPost();
        $params = $this->input->post(NULL,true);
        $this->validate([
            'city_id' => 'required|trim',
            'page_size' => 'required|trim',
            'page_num' => 'required|trim',
        ]);
        unset($params["to_group"]);
        $data=$this->alarmWorksheetService->getList($params);
        return $this->response($data);
    }

    //问题列表，（管理员、对应大队） 
    public function groupList()
    {
        $this->convertJsonToPost();
        $params = $this->input->post(NULL,true);
        $this->validate([
            'city_id' => 'required|trim',
            'to_group' => 'required|trim',
            'page_size' => 'required|trim',
            'page_num' => 'required|trim',
        ]);
        $list=$this->alarmWorksheetService->getList($params);
        return $this->response(["list"=>$list]);
    }

    //问题详情
    public function detail(){
        $this->convertJsonToPost();
        $params = $this->input->post(NULL,true);
        $this->validate([
            'id' => 'required|trim',
        ]);
        $detail=$this->alarmWorksheetService->find($params);
        return $this->response($detail);
    }

    //提交问题 
    public function deal()
    {
        $this->convertJsonToPost();
        $params = $this->input->post(NULL,true);
        $this->validate([
            'id' => 'required|trim',
            'deal_desc' => 'required|trim',
            'deal_pics' => 'trim',
            'deal_valuation' => 'trim',
            'deal_time' => 'required|trim',
        ]);
        $this->alarmWorksheetService->deal($params);
        return $this->response("");
    }

    //评价问题 
    public function valuation()
    {
        $this->convertJsonToPost();
        $params = $this->input->post(NULL,true);
        $this->validate([
            'id' => 'required|trim',
            'deal_valuation' => 'required|trim',
        ]);
        $this->alarmWorksheetService->valuation($params);
        return $this->response("");
    }

}
