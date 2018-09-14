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

    /**
     * 获取区域路口列表
     * @param city_id  interger Y 城市ID
     * @param area_id  interger Y 区域ID
     * @param type     interger Y 筛选条件：-1:全部；0:无配时；1:有配时；2:自适应；9:配时异常。默认全部
     * @return json
     */
    public function getAreaJunctionList()
    {
        $params = $this->input->post(NULL, TRUE);

        if (intval($params['city_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数city_id传递错误！';
            return;
        }

        if (intval($params['area_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数area_id传递错误！';
            return;
        }

        $type = -1;

        if (!empty($params['type']) && in_array(intval($params['type']), [-1, 0, 1, 2, 9])) {
            $type = intval($params['type']);
        }

        $data = [
            'city_id' => intval($params['city_id']),
            'area_id' => intval($params['area_id']),
            'type' => $type,
        ];

        $result = $this->timingadaptationarea_model->getAreaJunctionList($data);
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

    /**
     * 获取区域实时报警信息
     * @param city_id     interger Y 城市ID
     * @param area_id     interger Y 区域ID
     * @param alarm_type  interger N 报警类型：0：全部；1：过饱和；2：溢流。默认0
     * @param ignore_type interger N 忽略类型：0：全部；1：已忽略；2：未忽略。默认0
     * @return json
     */
    public function realTimeAlarmList()
    {
        $params = $this->input->post(NULL, TRUE);

        if (intval($params['city_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数city_id传递错误！';
            return;
        }

        if (intval($params['area_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数area_id传递错误！';
            return;
        }

        $alarm_type = 0;
        $ignore_type = 0;

        if (isset($params['alarm_type']) && in_array(intval($params['alarm_type']), [1, 2])) {
            $alarm_type = intval($params['alarm_type']);
        }
        if (isset($params['ignore_type']) && in_array(intval($params['ignore_type']), [1, 2])) {
            $ignore_type = intval($params['ignore_type']);
        }

        $data = [
            'city_id'     => intval($params['city_id']),
            'area_id'     => intval($params['area_id']),
            'alarm_type'  => $alarm_type,
            'ignore_type' => $ignore_type,
        ];

        $result = $this->timingadaptationarea_model->realTimeAlarmList($data);
        if ($result['errno'] != 0) {
            $this->errno = ERR_DEFAULT;
            $this->errmsg = $result['errmsg'];
            return;
        }

        $res['dataList'] = $result['data'] ?? (object)[];
        return $this->response($res);
    }
}
