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
            'road_id' => 'required',
            'task_type' => 'required',
        ]);

        $data = $this->opttaskService->RoadConflict($params);

        $this->response($data);
    }
}
