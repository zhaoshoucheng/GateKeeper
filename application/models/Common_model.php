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

        $res = [];
        foreach ($result['data']['junctions'] as $k=>$v) {
            $junctionName = $v['name'];
            $districtName = $v['district_name'];
            $road1 = $v['road1'] ?? '未知路口';
            $road2 = $v['road2'] ?? '未知路口';
            $res = [
                'logic_junction_id' => $v['logic_junction_id'],
                'junction_name'     => $v['name'],
                'lng'               => $v['lng'],
                'lat'               => $v['lat'],
            ];
        }
        $cityName = $result['data']['city_name'];

        $string = $junctionName . '路口位于';
        $string .= $cityName . $districtName . '，';
        $string .= '是' . $road1 . '和' . $road2 . '交叉的重要节点路口。';
        $res['desc'] = $string;
        if (empty($res)) {
            $res = (object)[];
        }

        return ['errno'=>0, 'data'=>$res];
    }

    /**
     * 获取路口相位信息
     * @param $data['city_id']           interger Y 城市ID
     * @param $data['logic_junction_id'] string   Y 路口ID
     * @return array
     */
    public function getJunctionMovements($data)
    {
        $result = ['errno'=>-1, 'errmsg'=>'', 'data'=>(object)[]];
        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空';
            return $result;
        }

        $flowsInfo = $this->waymap_model->getFlowsInfo($data['logic_junction_id']);
        if (!empty($flowsInfo)) {
            $result['data'] = $flowsInfo[$data['logic_junction_id']];
        }

        $result['errno'] = 0;

        return $result;
    }
}
