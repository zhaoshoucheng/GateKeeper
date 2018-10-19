<?php
/***************************************************************
 * # 干线类
 * # user:ningxiangbing@didichuxing.com
 * # date:2018-08-21
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\RoadService;

class Road extends MY_Controller
{
    protected $roadService;

    /**
     * Road constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('road_model');
        $this->load->config('junctioncomparison_conf');
        $this->load->config('evaluate_conf');

        $this->roadService = new RoadService();
    }

    /**
     * 查询干线列表
     *
     * @throws Exception
     */
    public function queryRoadList()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $data = $this->roadService->getRoadList($params);

        $this->response($data);
    }

    /**
     * 新增干线
     *
     * @throws Exception
     */
    public function addRoad()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'road_name' => 'nullunable',
            'road_direction' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        if (empty($params['junction_ids']) || !is_array($params['junction_ids'])) {
            throw new \Exception('参数 junction_ids 须为数组且不能为空', ERR_PARAMETERS);
        }

        if (!isset($params['junction_ids']) || count($params['junction_ids']) < 4) {
            throw new \Exception('请至少选择4个路口做为干线', ERR_PARAMETERS);
        }

        $roadDirectionConf = $this->config->item('road_direction');

        if (!array_key_exists($params['road_direction'], $roadDirectionConf)) {
            throw new \Exception('请选择正确的干线方向', ERR_PARAMETERS);
        }

        $params['city_id'] = intval($params['city_id']);

        $this->roadService->addRoad($params);
    }

    /**
     * 编辑干线
     *
     * @throws Exception
     */
    public function editRoad()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'road_name' => 'nullunable',
            'road_id' => 'nullunable',
            'road_direction' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        if (empty($params['junction_ids']) || !is_array($params['junction_ids'])) {
            throw new \Exception('参数 junction_ids 须为数组且不能为空', ERR_PARAMETERS);
        }

        if (!isset($params['junction_ids']) || count($params['junction_ids']) < 4) {
            throw new \Exception('请至少选择4个路口做为干线', ERR_PARAMETERS);
        }

        $roadDirectionConf = $this->config->item('road_direction');

        if (!array_key_exists($params['road_direction'], $roadDirectionConf)) {
            throw new \Exception('请选择正确的干线方向', ERR_PARAMETERS);
        }

        $params['city_id'] = intval($params['city_id']);

        $res = $this->roadService->updateRoad($params);

        if (!$res) {
            throw new Exception('干线更新失败', ERR_PARAMETERS);
        }
    }

    /**
     * 删除干线
     *
     * @throws Exception
     */
    public function delete()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'road_id' => 'nullunable',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $res = $this->roadService->deleteRoad($params);

        if (!$res) {
            throw new Exception('干线删除失败', ERR_PARAMETERS);
        }
    }

    /**
     * 查询干线详情
     *
     * @throws Exception
     */
    public function getRoadDetail()
    {
        $params = $this->input->post(null, true);

        // 校验参数
        $validator = Validator::make($params, [
            'city_id' => 'required',
            'road_id' => 'required',
        ]);

        if ($validator->fail()) {
            throw new \Exception($validator->firstError(), ERR_PARAMETERS);
        }

        $data = [
            'city_id' => intval($params['city_id']),
            'road_id' => strip_tags(trim($params['road_id'])),
        ];

        $data = $this->roadService->getRoadDetail($data);

        $this->response($data);
    }

    /**
     * 获取全部的干线信息
     *
     * @throws Exception
     */
    public function getAllRoadDetail()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $data = $this->roadService->getAllRoadDetail($params);

        $this->response($data);
    }

    /**
     * 获取干线评估指标
     */
    public function getQuotas()
    {
        $this->response($this->config->item('road'));
    }

    /**
     * 干线评估
     *
     * @throws Exception
     */
    public function comparison()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'road_id' => 'min:1',
            'quota_key' => 'min:1',
            'base_start_date' => 'date:Y-m-d',
            'base_end_date' => 'date:Y-m-d',
            'evaluate_start_date' => 'date:Y-m-d',
            'evaluate_end_date' => 'date:Y-m-d',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        if(!in_array($params['direction'], [1,2])) {
            throw new \Exception('参数 direction 必须为 1 或 2', ERR_PARAMETERS);
        }

        $data = $this->roadService->comparison($params);

        $this->response($data);
    }

    /**
     * 获取数据下载链接
     *
     * @throws Exception
     */
    public function downloadEvaluateData()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'download_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $data = $this->roadService->downloadEvaluateData($params);

        $this->response($data);
    }

    /**
     * Excel 文件下载
     *
     * @throws Exception
     */
    public function download()
    {
        $params = $this->input->get();

        $validate = Validate::make($params, [
            'download_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }
        $this->roadService->download($params);
    }
}
