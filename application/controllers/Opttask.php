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
            'task_type' => 'required',
            // 'page' => 'required',
            // 'page_size' => 'required',
        ]);

        if (!isset($params['page'])) {
            $params['page'] = 1;
        }
        if (!isset($params['page_size'])) {
            $params['page'] = 20;
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
            'task_id' => 'required|is_natural_no_zero',
            'action' => 'required',
        ]);

        $data = $this->opttaskService->UpdateTaskStatus($params);

        $this->response($data);
    }
}
