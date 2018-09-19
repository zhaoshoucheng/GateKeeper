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
        if (!isset($params['city_id']) || intval($params['city_id']) < 1) {
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

        if (empty($result['data'])) {
            $res = (object)[];
        } else {
            $res['dataList'] = $result['data'];
        }
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

        if (!isset($params['city_id']) || intval($params['city_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数city_id传递错误！';
            return;
        }

        if (!isset($params['area_id']) || intval($params['area_id']) < 1) {
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

        if (empty($result['data'])) {
            $res = (object)[];
        } else {
            $res['dataList'] = $result['data'];
        }
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

        if (!isset($params['city_id']) || intval($params['city_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数city_id传递错误！';
            return;
        }

        if (!isset($params['area_id']) || intval($params['area_id']) < 1) {
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

        if (empty($result['data'])) {
            $res = (object)[];
        } else {
            $res['dataList'] = $result['data'];
        }
        return $this->response($res);
    }

    /**
     * 人工标注报警信息
     * @param city_id           interger Y 城市ID
     * @param area_id           interger Y 区域ID
     * @param logic_junction_id string   Y 路口ID
     * @param logic_flow_id     string   Y 相位ID
     * @param is_correct        interger Y 是否正确 1：正确 2：错误
     * @param comment           string   N 备注信息
     */
    public function addAlarmRemark()
    {
        $params = $this->input->post(NULL, TRUE);

        if (!isset($params['city_id']) || intval($params['city_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数city_id传递错误！';
            return;
        }

        if (!isset($params['area_id']) || intval($params['area_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数area_id传递错误！';
            return;
        }

        if (empty($params['logic_junction_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数logic_junction_id传递错误！';
            return;
        }

        if (empty($params['logic_flow_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数logic_flow_id传递错误！';
            return;
        }

        if (empty($params['is_correct'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数is_correct传递错误！';
            return;
        }

        $comment = '';
        if (!empty($params['comment'])) {
            $comment = trim($params['comment']);
        }

        $data = [
            'city_id'           => intval($params['city_id']),
            'area_id'           => intval($params['area_id']),
            'logic_junction_id' => trim($params['logic_junction_id']),
            'logic_flow_id'     => trim($params['logic_flow_id']),
            'is_correct'        => intval($params['is_correct']),
            'comment'           => $comment,
        ];

        $result = $this->timingadaptationarea_model->addAlarmRemark($data);
        if ($result['errno'] != 0) {
            $this->errno = ERR_DEFAULT;
            $this->errmsg = $result['errmsg'];
            return;
        }

        if (empty($result['data'])) {
            $res = (object)[];
        } else {
            $res = $result['data'];
        }
        return $this->response($res);
    }

    /**
     * 忽略报警
     * @param city_id       interger Y 城市ID
     * @param area_id       interger Y 区域ID
     * @param logic_flow_id string   Y 相位ID
     * @return json
     */
    public function ignoreAlarm()
    {
        $params = $this->input->post(NULL, TRUE);

        if (!isset($params['city_id']) || intval($params['city_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数city_id传递错误！';
            return;
        }

        if (!isset($params['area_id']) || intval($params['area_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数area_id传递错误！';
            return;
        }

        if (empty($params['logic_flow_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数logic_flow_id传递错误！';
            return;
        }

        $data = [
            'city_id'           => intval($params['city_id']),
            'area_id'           => intval($params['area_id']),
            'logic_flow_id'     => trim($params['logic_flow_id']),
        ];

        $result = $this->timingadaptationarea_model->ignoreAlarm($data);
        if ($result['errno'] != 0) {
            $this->errno = ERR_DEFAULT;
            $this->errmsg = $result['errmsg'];
            return;
        }

        if (empty($result['data'])) {
            $res = (object)[];
        } else {
            $res = $result['data'];
        }
        return $this->response($res);
    }

    /**
     * 更新自适应路口开关
     * @param logic_junction_id string   Y 路口ID
     * @param area_id           interger Y 区域ID
     * @param is_upload         interger Y 变更状态 0：关闭；1：打开
     * @return json
     */
    public function junctionSwitch()
    {
        $params = $this->input->post(NULL, TRUE);

        if (!isset($params['area_id']) || intval($params['area_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数area_id传递错误！';
            return;
        }

        if (!isset($params['is_upload']) || !in_array(intval($params['is_upload']), [0, 1])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数is_upload传递错误！';
            return;
        }

        if (empty($params['logic_junction_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数logic_junction_id传递错误！';
            return;
        }

        $data = [
            'is_upload'         => intval($params['is_upload']),
            'area_id'           => intval($params['area_id']),
            'logic_junction_id' => trim($params['logic_junction_id']),
        ];

        $result = $this->timingadaptationarea_model->junctionSwitch($data);
        if ($result['errno'] != 0) {
            $this->errno = ERR_DEFAULT;
            $this->errmsg = $result['errmsg'];
            return;
        }

        if (empty($result['data'])) {
            $res = (object)[];
        } else {
            $res = $result['data'];
        }
        return $this->response($res);
    }

    /**
     * 更新自适应区域开关
     * @param city_id   interger Y 城市ID
     * @param area_id   interger Y 区域ID
     * @param is_upload interger Y 变更状态 0：关闭；1：打开
     * @return json
     */
    public function areaSwitch()
    {
        $params = $this->input->post(NULL, TRUE);

        if (!isset($params['city_id']) || intval($params['city_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数city_id传递错误！';
            return;
        }

        if (!isset($params['area_id']) || intval($params['area_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数area_id传递错误！';
            return;
        }

        if (!isset($params['is_upload']) || !in_array(intval($params['is_upload']), [0, 1])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数is_upload传递错误！';
            return;
        }

        $data = [
            'is_upload' => intval($params['is_upload']),
            'area_id'   => intval($params['area_id']),
            'city_id'   => intval($params['city_id']),
        ];

        $result = $this->timingadaptationarea_model->areaSwitch($data);
        if ($result['errno'] != 0) {
            $this->errno = ERR_DEFAULT;
            $this->errmsg = $result['errmsg'];
            return;
        }

        if (empty($result['data'])) {
            $res = (object)[];
        } else {
            $res = $result['data'];
        }
        return $this->response($res);
    }

    /**
     * 获取区域指标折线图
     * @param city_id   interger Y 城市ID
     * @param area_id   interger Y 区域ID
     * @param quota_key string   Y 指标KEY speed / stopDelay
     * @return json
     */
    public function getAreaQuotaInfo()
    {
        $params = $this->input->post(NULL, TRUE);

        if (!isset($params['city_id']) || intval($params['city_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数city_id传递错误！';
            return;
        }

        if (!isset($params['area_id']) || intval($params['area_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数area_id传递错误！';
            return;
        }

        if (empty($params['quota_key']) || !in_array(trim($params['quota_key']), ['avgSpeed', 'stopDelay'], true)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数quota_key传递错误！';
            return;
        }

        $data = [
            'quota_key' => trim($params['quota_key']),
            'area_id'   => intval($params['area_id']),
            'city_id'   => intval($params['city_id']),
        ];

        $result = $this->timingadaptationarea_model->getAreaQuotaInfo($data);
        if ($result['errno'] != 0) {
            $this->errno = ERR_DEFAULT;
            $this->errmsg = $result['errmsg'];
            return;
        }

        if (empty($result['data'])) {
            $res = (object)[];
        } else {
            $res['dataList'] = $result['data'];
        }
        return $this->response($res);
    }

    /**
     * 获取时空图
     * @param city_id           interger Y 城市ID
     * @param logic_junction_id string   Y 路口ID
     * @param logic_flow_id     string   Y 相位ID
     * @return json
     */
    public function getSpaceTimeMtraj()
    {
        $params = $this->input->post(NULL, TRUE);

        if (!isset($params['city_id']) || intval($params['city_id']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数city_id传递错误！';
            return;
        }

        if (empty($params['logic_junction_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数logic_junction_id传递错误！';
            return;
        }

        if (empty($params['logic_flow_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数logic_flow_id传递错误！';
            return;
        }

        $data = [
            'city_id'           => intval($params['city_id']),
            'logic_junction_id' => trim($params['logic_junction_id']),
            'logic_flow_id'     => trim($params['logic_flow_id']),
        ];

        $result = $this->timingadaptationarea_model->getSpaceTimeMtraj($data);
        if ($result['errno'] != 0) {
            $this->errno = ERR_DEFAULT;
            $this->errmsg = $result['errmsg'];
            return;
        }

        if (empty($result['data'])) {
            $res = (object)[];
        } else {
            $res['dataList'] = $result['data'];
        }
        return $this->response($res);
    }
}
