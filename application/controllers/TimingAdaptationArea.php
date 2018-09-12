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
     * @return json
     */
    public function getAreaList()
    {
        $params = $this->input->post(NULL, TRUE);
        if (intval($params['city_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数city_id传递错误！';
            return;
        }

        $data['city_id'] = intval($params['city_id']);

        $result = $this->timingadaptationarea_model->getAreaList($data);
        if (empty($result)) {
            $res['dataList'] = (object)[];
            return $this->response($res);
        }

        if ($result['errno'] != 0) {
            $this->errno = ERR_DEFAULT;
            $this->errmsg = $result['errmsg'];
            return;
        }

        $res['dataList'] = $result['data'] ?? (object)[];
        return $this->response($res);
    }
}