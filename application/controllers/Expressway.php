<?php


/***************************************************************
# 快速路需求
# user:zhuyewei@didichuxing.com
# date:2019-11-18
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\ExpresswayService;


class Expressway extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->expresswayService = new ExpresswayService();

    }

    /*
     * 快速路概览
     * */
    public function overview(){
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);
        //查询路口列表
       $data = $this->expresswayService->queryOverview($params['city_id']);

        //TOOD 暂时写死
        $data['center']=[
            "lat"=>32.006187833115,
            "lng"=>118.81212011359
        ];
        $data['upramp']=0;
        $data['downramp']=0;
//        $juncTypeMap = [];
        foreach ($data['junc_list'] as  $jc) {
//            $juncTypeMap[$jc['junction_id']] = $jc['type'];
            if($jc['type']==1){
                $data['upramp']+=1;
            }elseif ($jc['type']==2) {
                $data['downramp']+=1;
            }
        }

        foreach ($data['road_list'] as $key => $value) {
//            if(isset($juncTypeMap[$value['start_junc']]) && $juncTypeMap[$value['start_junc']] == 1){
//                $juncNameMap[$value['start_junc']] = $value['name']."上匝道";
//            }elseif (isset($juncTypeMap[$value['end_junc']]) && $juncTypeMap[$value['end_junc']] == 2) {
//                $juncNameMap[$value['end_junc']] = $value['name']."下匝道";
//            }

            $str = substr($value['geom'],12,-1);
            $lineArray = explode(",", $str);
            $ls = [];
            foreach ($lineArray as $lv) {
                $ls[] =array_map('floatval', explode(" ", $lv));
            }

            // $linestring = new \GeoJson\Geometry\LINESTRING($linestrings);
            $data['road_list'][$key]['geom'] = [
                'type'=>"LineString",
                "coordinates"=>$ls
            ];
        }
//        foreach ($data['junc_list'] as $jk => $jc) {
//            if(isset($juncNameMap[$jc['junction_id']])){
//                $data['junc_list'][$jk]['name'] = $juncNameMap[$jc['junction_id']];
//            }
//        }

        $this->response($data);
    }

    /*
     * 快速路拥堵概览
     * */
    public function stopDelayTopList(){
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);
        //查询路口列表
        $data = $this->expresswayService->queryStopDelayList($params['city_id']);

        $this->response($data);
    }

    /*
     * 快速路指标详情
     * */
    public function detail(){
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'start_junc_id' => 'required',
            'end_junc_id' => 'required',
            'time'=>'required',
        ]);
        //查询路口列表
        $data = $this->expresswayService->queryQuotaDetail($params);

        $this->response($data);
    }

    /*
     * 快速路报警列表
     * */
    public function alarmlist() {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);
        //查询路口列表
        $data = $this->expresswayService->alarmlist($params);

        $this->response($data);
    }


    /*
     * 快速路路况
     * */
    public function condition() {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);
        //查询路口列表
        $data = $this->expresswayService->condition($params);

        $this->response($data);
    }

}