<?php
/***************************************************************
# 干线配时类
# user:zhuyewei@didichuxing.com
# date:2018-06-29
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Arterialtiming extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('arterialtiming_model');
    }

    public function queryArterialTimingInfo()
    {
        $params = $this->input->post();

        // 校验参数
        $validate = Validate::make($params, [
                'junction_infos'    => 'nullunable',
                'time_point'        => 'nullunable',
                'dates'             => 'nullunable',
            ]
        );
        if(!$validate['status']){
            return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
        }
        $data = $params['junction_infos'];
        $data = json_decode($data,true);

        $timePoint = $params['time_point'];
        $date = $params['dates'];
        $date = json_decode($date,true);
        $timingInfo = $this->arterialtiming_model->getJunctionTimingInfos($data,$timePoint,end($date));
        return $this->response($timingInfo);
    }

    public function queryArterialJunctionInfo()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
                'city_id'               => 'nullunable',
//                'selected_junctionids'  => 'nullunable',
                'map_version'           => 'nullunable',
            ]
        );
        if(!$validate['status']){
            return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
        }

        $cityId = $params['city_id'];
        $version = $params['map_version'];
        $selectJunctions = $params['selected_junctionids'];

//        $selectJunctions = json_decode($selectJunctions,true);
        $ret = $this->arterialtiming_model->getJunctionInfos($cityId,$version,$selectJunctions);

        return $this->response($ret);
    }
}