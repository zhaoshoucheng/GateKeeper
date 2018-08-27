<?php
/********************************************
# desc:    公共方法模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-08-23
********************************************/

class Common_model extends CI_Model
{
    private $tb = '';
    private $db = '';

    public function __construct()
    {
        parent::__construct();

        $this->load->model('waymap_model');
    }

    /**
     * 获取路口所属行政区域及交叉节点信息
     * @param $data['city_id']           interger Y 城市ID
     * @param $data['logic_junction_id'] string   Y 路口ID
     * @param $data['map_version']       string   Y 地图版本
     * @return array
     */
    public function getJunctionAdAndCross($data)
    {
        $result = $this->waymap_model->gitJunctionDetail($data);
        if ($result['errno'] != 0) {
            return ['errno'=>ERR_REQUEST_WAYMAP_API, 'errmsg'=>$result['errmsg']];
        }

        if (empty($result['data']['junctions'])) {
            $errmsg = '路网没有返回路口详情！';
            return ['errno'=>ERR_REQUEST_WAYMAP_API, 'errmsg'=>$errmsg];
        }

        foreach ($result['data']['junctions'] as $k=>$v) {
            $junctionName = $v['name'];
            $districtName = $v['district_name'];
            $road1 = $v['road1'] ?? '未知路口';
            $road2 = $v['road2'] ?? '未知路口';
        }
        $cityName = $result['data']['city_name'];

        $string = $junctionName . '路口位于';
        $string .= $cityName . '市' . $districtName . '区，';
        $string .= '是' . $road1 . '和' . $road2 . '交叉的重要节点路口';

        return ['errno'=>0, 'data'=>$string];
    }
}