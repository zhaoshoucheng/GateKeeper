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

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);

        $data = $this->roadService->getRoadList($params);

        $this->response($data);
    }

    /**
     * 由干线自增id获取干线子路口
     *
     * @throws Exception
     */
    public function batchGetJunctions()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'road_ids' => 'required',
        ]);

        $params['road_ids'] = explode(';', $params['road_ids']);

        $data = $this->roadService->getJunctionsByRoadID($params);

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

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'road_name' => 'required|trim|min_length[1]',
            'junction_ids[]' => 'required',
        ]);

        if (count($params['junction_ids']) < 2) {
            throw new \Exception('请至少选择2个路口做为干线', ERR_PARAMETERS);
        }

        $res = $this->roadService->addRoad($params);

        if (!$res) {
            throw new Exception('干线创建失败', ERR_DATABASE);
        }

        $this->response($res);
    }

    /**
     * 编辑干线
     *
     * @throws Exception
     */
    public function editRoad()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'road_name' => 'required|trim|min_length[1]',
            'road_id' => 'required|trim|min_length[1]',
            'junction_ids[]' => 'required',
        ]);

        if (count($params['junction_ids']) < 2) {
            throw new \Exception('请至少选择2个路口做为干线', ERR_PARAMETERS);
        }

        $data = $this->roadService->updateRoad($params);

        $this->response($data);
    }

    /**
     * 删除干线
     *
     * @throws Exception
     */
    public function delete()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'road_id' => 'required|min_length[1]'
        ]);

        $data = $this->roadService->deleteRoad($params);

        $this->response($data);
    }

    /**
     * 查询干线详情
     *
     * @throws Exception
     */
    public function getRoadDetail()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'road_id' => 'required|min_length[1]',
        ]);

        $data = $this->roadService->getRoadDetail($params);

        $this->response($data);
    }

    public function getPathHeadTailJunction(){
        $params = $this->input->post(null, true);
        $this->validate([
            'junction_ids' => 'required|min_length[1]',
            'city_id'=> 'required|min_length[1]',
        ]);
        $data = $this->roadService->getPathHeadTailJunction($params);
        $this->response(["junction_list"=>$data]);
    }

    /**
     * 获取全部的干线信息
     *
     * @throws Exception
     */
    public function getAllRoadDetail()
    {
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'show_type' => 'required|in_list[0,1]',
        ]);
        $data = $this->roadService->getAllRoadDetail($params);

        // 根据权限做干线过滤
        if (!in_array($params['city_id'], $this->userPerm['city_id'])) {
            $roadIds = $this->userPerm['route_id'];
            $data = array_values(array_filter($data, function($item) use($roadIds){
                if (in_array($item['road_id'], $roadIds)) {
                    return true;
                }
                return false;
            }));
        }

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

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'road_id' => 'required|min_length[1]',
            'quota_key' => 'required|min_length[1]',
            'base_start_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'base_end_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'evaluate_start_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'evaluate_end_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'direction' => 'required|in_list[1,2]'
        ]);

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

        $this->validate([
            'download_id' => 'required|min_length[1]'
        ]);

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

        if (empty($params['download_id'])) {
            throw new \Exception('参数download_id不能为空！', ERR_PARAMETERS);
        }

        $this->roadService->download($params);
    }
}
