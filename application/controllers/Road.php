<?php
/***************************************************************
# 干线类
# user:ningxiangbing@didichuxing.com
# date:2018-08-21
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Road extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('road_model');
        $this->load->config('junctioncomparison_conf');
    }

    /**
     * 查询干线列表
     * @param city_id interger Y 城市ID
     * @return json
     */
    public function queryRoadList()
    {
        $params = $this->input->post();

        // 校验参数
        $validate = Validate::make($params, [
                'city_id'   => 'min:1',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $cityId = intval($params['city_id']);

        $result = $this->road_model->queryRoadList($cityId);

        return $this->response($result);
    }

    /**
     * 新增干线
     * @param city_id        interger Y 城市ID
     * @param road_name      string   Y 干线名称
     * @param junction_ids   array    Y 干线路口ID
     * @param road_direction interger Y 干线方向 1：东西 2：南北
     * @return json
     */
    public function addRoad()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $validate = Validate::make($params, [
                'city_id'        => 'min:1',
                'road_name'      => 'nullunable',
                'road_direction' => 'min:1',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (empty($params['junction_ids']) || !is_array($params['junction_ids'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数 junction_ids 须为数组且不能为空！';
            return;
        }

        if (count($params['junction_ids']) < 4) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '请至少选择4个路口做为干线！';
            return;
        }

        $roadDirectionConf = $this->config->item('road_direction');
        if (!array_key_exists(intval($params['road_direction']), $roadDirectionConf)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '请选择正确的干线方向！';
            return;
        }

        $data = [
            'city_id'        => intval($params['city_id']),
            'road_name'      => strip_tags(trim($params['road_name'])),
            'junction_ids'   => $params['junction_ids'],
            'road_direction' => intval($params['road_direction']),
        ];

        $result = $this->road_model->addRoad($data);
        if ($result['errno'] != 0) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $result['errmsg'];
            return;
        }

        $this->errmsg = 'success.';
        return;
    }

    /**
     * 编辑干线
     * @param city_id        interger Y 城市ID
     * @param road_id        string   Y 干线ID
     * @param road_name      string   Y 干线名称
     * @param junction_ids   array    Y 干线路口ID
     * @param road_direction interger Y 干线方向 1：东西 2：南北
     * @return json
     */
    public function editRoad()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $validate = Validate::make($params, [
                'city_id'        => 'min:1',
                'road_name'      => 'nullunable',
                'road_id'        => 'nullunable',
                'road_direction' => 'min:1',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (empty($params['junction_ids']) || !is_array($params['junction_ids'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数 junction_ids 须为数组且不能为空！';
            return;
        }

        if (count($params['junction_ids']) < 4) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '请至少选择4个路口做为干线！';
            return;
        }

        $roadDirectionConf = $this->config->item('road_direction');
        if (!array_key_exists(intval($params['road_direction']), $roadDirectionConf)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '请选择正确的干线方向！';
            return;
        }

        $data = [
            'city_id'        => intval($params['city_id']),
            'road_id'        => strip_tags(trim($params['road_id'])),
            'road_name'      => strip_tags(trim($params['road_name'])),
            'junction_ids'   => $params['junction_ids'],
            'road_direction' => intval($params['road_direction']),
        ];

        $result = $this->road_model->editRoad($data);

        if ($result['errno'] != 0) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $result['errmsg'];
            return;
        }

        $this->errmsg = 'success.';
        return;
    }

    /**
     * 删除干线
     * @param city_id interger Y 城市ID
     * @param road_id string   Y 干线ID
     * @return json
     */
    public function delete()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $validate = Validate::make($params, [
                'city_id' => 'min:1',
                'road_id' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = [
            'city_id' => intval($params['city_id']),
            'road_id' => strip_tags(trim($params['road_id'])),
        ];

        $result = $this->road_model->delete($data);
        if ($result['errno'] != 0) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $result['errmsg'];
            return;
        }

        $this->errmsg = 'success.';
        return;
    }

    /**
     * 查询干线详情
     * @param city_id interger Y 城市ID
     * @param road_id string   Y 干线ID
     * @return json
     */
    public function getRoadDetail()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $validate = Validate::make($params, [
                'city_id' => 'min:1',
                'road_id' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = [
            'city_id' => intval($params['city_id']),
            'road_id' => strip_tags(trim($params['road_id'])),
        ];

        $result = $this->road_model->getRoadDetail($data);

        return $this->response($result);
    }
}
