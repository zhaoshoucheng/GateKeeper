<?php
/***************************************************************
 * # 区域管理
 * # user:niuyufu@didichuxing.com
 * # date:2018-08-23
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\OpttaskService;

class Opttask extends MY_Controller
{
    protected $opttaskService;

    /**
     * Opttask constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->opttaskService = new OpttaskService();
    }

    /**
     * 任务列表
     *
     * @throws Exception
     */
    public function list()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'task_type' => 'required|is_natural',
            // 'page' => 'required',
            // 'page_size' => 'required',
        ]);

        if (!isset($params['page'])) {
            $params['page'] = 1;
        }
        if (!isset($params['page_size'])) {
            $params['page_size'] = 20;
        }

        $data = $this->opttaskService->TaskList($params);

        $this->response($data);
    }

     /**
     * 修改任务状态
     *
     * @throws Exception
     */
    // action   int 1 开始 2 暂停 3 删除
    public function action()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'task_id' => 'required|is_natural_no_zero',
            'action' => 'required|is_natural',
        ]);

        $taskInfo = $this->opttaskService->TaskInfo($params);
        $data = $this->opttaskService->UpdateTaskStatus($params);

        if ($data != true) {
            $this->errno = -1;
            $this->errmsg = "操作失败";
        }

        //log
        if(in_array($params["action"], [0,1])){
            $taskName = $taskInfo["task_name"] ?? "";
            $taskAction = "";
            if($params["action"]==0){
                $taskAction = "开始";
            }
            if($params["action"]==1){
                $taskAction = "暂停";
            }
            $actionLog = sprintf("任务ID：%s，任务名称：%s，任务状态：%s",$params["task_id"],$taskName,$taskAction);
            $this->insertLog("任务管理","修改任务状态","编辑",$params,$actionLog);
            $this->response("");
        }
        if(in_array($params["action"], [2])){
            $taskName = $taskInfo["task_name"] ?? "";
            $taskAction = "";
            $actionLog = sprintf("任务ID：%s，任务名称：%s",$params["task_id"],$taskName);
            $this->insertLog("任务管理","删除任务","编辑",$params,$actionLog);
            $this->response("");
        }
    }
}
