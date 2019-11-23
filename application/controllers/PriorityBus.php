<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Services\RoadService;
class PriorityBus extends MY_Controller
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
     * 获取全部的干线信息
     *
     * @throws Exception
     */
    public function getAllRoadList()
    {
        $this->convertJsonToPost();
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);
        $params["show_type"]=0;
        $data = $this->roadService->getBusRoadList($params);
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
}
