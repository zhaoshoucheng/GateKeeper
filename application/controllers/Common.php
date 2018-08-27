<?php
/***************************************************************
# 公共方法类
# 1、获取路口所属行政区域及交叉节点信息
# user:ningxiangbing@didichuxing.com
# date:2018-08-23
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Common extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('common_model');
    }

    /**
     * 获取路口所属行政区域及交叉节点信息
     * @param city_id           interger Y 城市ID
     * @param logic_junction_id string   Y 路口ID
     * @param map_version       string   Y 地图版本
     * @return json
     */
    public function getJunctionAdAndCross()
    {
        $params = $this->input->post();

        // 校验参数
        $validate = Validate::make($params, [
                'city_id'             => 'min:1',
                'logic_junction_id'   => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data['city_id'] = intval($params['city_id']);
        $data['logic_junction_id'] = strip_tags(trim($params['logic_junction_id']));

        if (!empty($params['map_version'])) {
            $data['map_version'] = $params['map_version'];
        }

        $result = $this->common_model->getJunctionAdAndCross($data);
        if ($result['errno'] != 0) {
            $this->errno = $result['errno'];
            $this->errmsg = $result['errmsg'];
            return;
        }

        return $this->response($result['data']);
    }
}