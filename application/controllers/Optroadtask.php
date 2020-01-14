<?php
/***************************************************************
 * # 区域管理
 * # user:niuyufu@didichuxing.com
 * # date:2018-08-23
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\OpttaskService;

class Optroadtask extends MY_Controller
{
    protected $opttaskService;

    /**
     * Optroadtask constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->opttaskService = new OpttaskService();
    }

    /**
     * 创建，修改任务
     *
     * @throws Exception
     */
    public function update()
    {
        $params = $this->input->raw_input_stream;
        $params = json_decode($params, true);
        // if (empty($params) or !$this->CheckTaskConf($params)) {
        //     $this->errno = -1;
        //     $this->errmsg = "输入错误";
        //     $this->response("");
        //     return;

        // }

        $data = $this->opttaskService->UpdateTask($params);
        if ($data == false) {
            $this->errno = -1;
            $this->errmsg = "操作失败";
        }
        if(empty($params["task_id"])){
            $params["task_id"] = $data;
            $actionLog = sprintf("任务ID：%s，任务名称：%s",$params["task_id"],$params["task_name"]);
            $this->insertLog("任务管理","新增干线优化任务","新增",$params,$actionLog);
        }else{
            $actionLog = sprintf("任务ID：%s，任务名称：%s",$params["task_id"],$params["task_name"]);
            $this->insertLog("任务管理","编辑干线优化任务","编辑",$params,$actionLog);
        }
        $this->response("");
    }

    private function CheckTaskConf($task) {
        $items = ['task_id', 'city_id', 'task_name', 'task_type', 'road_id', 'junction', 'plan', 'timing'];
        foreach ($items as $item) {
            if (!isset($task[$item])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 更新区域及路口
     *
     * @throws Exception
     */
    public function info()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'task_id' => 'required|is_natural_no_zero',
        ]);

        $data = $this->opttaskService->TaskInfo($params);

        $this->response($data);
    }

    /**
     * 任务干线路口冲突
     *
     * @throws Exception
     */
    public function roadconflict()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'road_id' => 'required|trim|min_length[1]',
            'task_type' => 'required|is_natural',
        ]);

        $data = $this->opttaskService->RoadConflict($params);

        $this->response($data);
    }

    /**
     * 任务干线路口冲突
     *
     * @throws Exception
     */
    public function search()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'keyword' => 'required|trim|min_length[1]',
        ]);

        $data = $this->opttaskService->SearchRoad($params);

        $this->response($data);
    }

    /**
     * 获取结果列表
     *
     * @throws Exception
     */
    public function list()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'task_id' => 'required|is_natural_no_zero',
        ]);

        $data = $this->opttaskService->ResultList($params);

        $this->response($data);
    }

    /**
     * 根据结果id获取任务配置详情，因为可能会变化，所以快照存储
     *
     * @throws Exception
     */
    public function resulttaskinfo()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'result_id' => 'required|is_natural_no_zero',
        ]);

        $data = $this->opttaskService->ResultTaskInfo($params);

        $this->response($data);
    }

    // getSpaceTimeDiagram
    public function getSpaceTimeDiagramArg()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'result_id' => 'required|is_natural_no_zero',
        ]);

        $params['field'] = 'diagram_arg';
        $data = $this->opttaskService->GetResultField($params);

        $this->response($data);
    }
    // getRoadDetail
    public function getRoadDetail()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'result_id' => 'required|is_natural_no_zero',
        ]);

        $params['field'] = 'road_info';
        $data = $this->opttaskService->GetResultField($params);

        $this->response($data);
    }
    // queryarterialjunctionInfo
    public function queryarterialjunctionInfo()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'result_id' => 'required|is_natural_no_zero',
        ]);

        $params['field'] = 'junction_info';
        $data = $this->opttaskService->GetResultField($params);

        $this->response($data);
    }
    // queryarterialtiminginfo
    public function queryarterialtiminginfo()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'result_id' => 'required|is_natural_no_zero',
        ]);

        $params['field'] = 'timing_info';
        $data = $this->opttaskService->GetResultField($params);

        $this->response($data);
    }
    // opt getClockShiftCorrect
    public function getTaskClockShiftCorrect()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'result_id' => 'required|is_natural_no_zero',
        ]);

        $params['field'] = 'clockshift_info';
        $data = $this->opttaskService->GetResultField($params);

        $this->response($data);
    }
    // queryGreenWaveOptPlan
    public function queryGreenWaveOptPlan()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'result_id' => 'required|is_natural_no_zero',
        ]);

        $params['field'] = 'opt_result';
        $data = $this->opttaskService->GetResultField($params);

        $this->response($data);
    }
}
