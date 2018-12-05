<?php
/***************************************************************
# 参数管理
# user:ningxiangbing@didichuxing.com
# date:2018-11-19
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\ParametermangeService;

class Parametermange extends MY_Controller
{
    protected $parametermangeService;

    public function __construct()
    {
        parent::__construct();

        $this->parametermangeService = new parametermangeService();
    }

    /**
     * 获取参数列表
     * @param $params['city_id'] int    Y 城市ID
     * @param $params['area_id'] int    Y 区域ID
     * @param $params['is_default'] int    Y 1:默认, 2:非默认
     * @throws Exception
     */
    public function paramList()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'city_id'    => 'required|is_natural_no_zero',
            'area_id'    => 'required|is_natural_no_zero',
            'is_default' => 'required|in_list[0, 1]',
        ]);

        $data = $this->parametermangeService->paramList($params);
        $this->response($data);
    }
}
