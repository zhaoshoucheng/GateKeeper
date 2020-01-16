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
        $this->load->model('waymap_model');
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

        //操作日志
        $juncNames = $this->waymap_model->getJunctionNames(implode(",",$params["junction_ids"]));
        $actionLog = sprintf("干线ID:%s 干线名称：%s，路口名称列表：%s",$res,$params["road_name"],implode(",",$juncNames));
        $this->insertLog("路口管理","新增干线","新增",$params,$actionLog);
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

        //操作日志
        $roadInfo = $this->roadService->getRoadInfo($params["road_id"]);
        $oldJuncIds = explode(",",$roadInfo["logic_junction_ids"]);
        $newJuncIds = $params["junction_ids"];
        $interJuncIds=array_intersect($oldJuncIds,$newJuncIds);
        $delJuncIds = [];
        $addJuncIds = [];
        foreach($oldJuncIds as $oldJuncId){
            if(!in_array($oldJuncId,$newJuncIds)){
                $delJuncIds[] = $oldJuncId;
            }
        }
        foreach($newJuncIds as $newJuncId){
            if(!in_array($newJuncId,$oldJuncIds)){
                $addJuncIds[] = $newJuncId;
            }
        }
        
        // print_r($addJuncIds);
        $addJuncNames = $this->waymap_model->getJunctionNames(implode(",",$addJuncIds));
        $delJuncNames = $this->waymap_model->getJunctionNames(implode(",",$delJuncIds));
        // print_r($delJuncIds);
        // print_r($addJuncNames);
        // print_r($delJuncNames);exit;
        $actionLog = sprintf("干线ID：%s，干线名称：%s，新增路口：%s，删除路口：%s",$params["road_id"],$roadInfo["road_name"],implode(",",$addJuncNames),implode(",",$delJuncNames));
        $this->insertLog("路口管理","编辑干线路口","编辑",$params,$actionLog);
        $data = $this->roadService->updateRoad($params);
        $this->response($data);
    }

    //道路施工信息,暂时写死
    public function queryConstructionInfo(){
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);


        $data = [
            'road_info'=>[],
            "Point_info"=>[
                [
                    "lat"=> "32.00781",
                    "lng"=> "118.73584",
                    "detail"=> [
                      "title"=> "占道封闭施工",
                      "address"=> "汉中门大街西延的“浦江”至“清河”段",
                      "time"=> "2019/10/20 - 2019/10/30",
                      "company"=> "小桔科技",
                      "tel"=> "18800006666"
                    ]
                ]
            ]
        ];
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

        //操作日志
        $roadInfo = $this->roadService->getRoadInfo($params["road_id"]);
        $actionLog = sprintf("干线ID：%s，干线名称：%s",$params["road_id"],$roadInfo["road_name"]);
        $this->insertLog("路口管理","删除干线","删除",$params,$actionLog);

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

        // 有当前城市的权限,则干线无需过滤
        if (!empty($this->userPerm) && empty($this->userPerm["city_id"])) {
            $roadIds = $this->userPerm['route_id'];
            if(!empty($roadIds)){
                $data = array_values(array_filter($data, function($item) use($roadIds){
                    if (in_array($item['road_id'], $roadIds)) {
                        return true;
                    }
                    return false;
                }));
            }else{
                $data = [];
            }
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

    public function comparisonTable(){
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'road_id' => 'required|min_length[1]',
//            'quota_key' => 'required|min_length[1]',
            'base_start_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'base_end_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'time_point'=>'required'
//            'evaluate_start_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
//            'evaluate_end_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
//            'direction' => 'required|in_list[1,2]'
        ]);

        $data = $this->roadService->comparisonTable($params);

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

    public function cityRoadsOutter() {
        $params = $this->input->get();

        if (empty($params['city_id'])) {
            throw new \Exception('参数city_id不能为空！', ERR_PARAMETERS);
        }

        $data = $this->roadService->cityRoadsOutter($params);
        $this->response($data);
    }

    public function roadInfo() {
        $params = $this->input->get();

        if (empty($params['road_id'])) {
            throw new \Exception('参数road_id不能为空！', ERR_PARAMETERS);
        }

        $data = $this->roadService->roadInfo($params);
        $this->response($data);
    }

    /*
     * 干线绿波分析,南京项目使用
     *
     * */
    public function greenWaveAnalysis(){
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);

        $data = $this->roadService->greenWaveAnalysis($params['city_id']);

        $this->response($data);
    }
}
