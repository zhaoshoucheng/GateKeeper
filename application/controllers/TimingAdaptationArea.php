<?php
/***************************************************************
# 自适应
# user:ningxiangbing@didichuxing.com
# date:2018-09-10
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class TimingAdaptationArea extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('timingadaptationarea_model');
    }

    /**
     * 获取自适应区域列表
     * @param city_id    interger Y 城市ID
     */
    public function getAreaList()
    {
        $params = $this->input->post(NULL, TRUE);
        if (intval($params['city_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数city_id传递错误！';
            return;
        }

        // 调用signal-mis接口
        try {
            $url = $this->config->item('signal_mis_interface') . '/TimingAdaptation/getAreaList';
            $data['city_id'] = intval($params['city_id']);

            $result = httpPOST($url, $data);
            if (empty($result)) {
                return (object)[];
            }
            $result = json_decode($result, true);
            if ($result['errorCode'] != 0) {
                $this->errno = ERR_DEFAULT;
                $this->errmsg = $result['errorMsg'];
                return;
            }
            $res['dataList'] = $result['data'] ?? (object)[];
            return $this->response($res);
        } catch (Exception $e) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '调用signal-mis的getAreaList接口出错！';
            return;
        }
    }
}