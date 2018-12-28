<?php
/***************************************************************
# 参数管理
# user:ningxiangbing@didichuxing.com
# date:2018-11-19
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\ParametermanageService;

class Parametermanage extends MY_Controller
{
    protected $parametermanageService;

    public function __construct()
    {
        parent::__construct();

        $this->parametermanageService = new parametermanageService();
    }

    /**
     * 获取参数列表
     * @param $params['city_id'] int    Y 城市ID
     * @param $params['area_id'] int    Y 区域ID
     * @param $params['is_default'] int    Y 1:默认, 0:非默认
     * @throws Exception
     */
    public function paramList()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'city_id'    => 'required|is_natural_no_zero',
            'area_id'    => 'required|integer',
            'is_default' => 'required|in_list[0,1]',
        ]);

        try {
            $data = $this->parametermanageService->paramList($params);
            $this->response($data);
        } catch (Exception $e) {
            $this->response('', 500, $e);
        }
    }

    /**
     * 获取优化参数配置阀值
     * @param $params['city_id'] int    Y 城市ID
     * @throws Exception
     */
    public function paramLimit()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'city_id'    => 'required|is_natural_no_zero',
        ]);

        $data = $this->parametermanageService->paramLimit($params);
        $this->response($data);
    }

    /**
     * 更新优化参数配置阀值
     *
     * @throws Exception
     */
    public function editParam()
    {
        $params = file_get_contents("php://input");
        $json = json_decode($params, true);
		$json = $this->security->xss_clean($json);

        try {
            $this->parametermanageService->updateParam($json);
            $this->response('');
        } catch (Exception $e) {
            $this->response('', 500, $e);
        }
    }
}
