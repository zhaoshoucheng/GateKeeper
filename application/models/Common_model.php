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

        $result['data'] = $this->sortByNema($result['data']);

        $result['errno'] = 0;

        return $result;
    }


    /**
     * @param $flowInfos array['logic_flow_id' => 'direction']
     * @return array [['flow_id', 'flow_name', 'order']]
     */
    public function sortByNema($flowInfos)
    {
        // 过滤有"掉头"字样的
        $flowInfos = array_filter($flowInfos, function($direction) {
            return strpos($direction, '掉头') !== False;
        });

        // nema排序
        $num1 = [
            '北' => 4 * 10,
            '东' => 3 * 10,
            '南' => 2 * 10,
            '西' => 1 * 10,
        ];
        $num2 = [
            '左' => 5 * 1,
            '直' => 4 * 1,
            '右' => 3 * 1,
        ];

        $ret = [];
        foreach ($flowInfos as $flowId => $direction) {
            $score1 = $num1[$direction[0]] ?? 0;
            $score2 = $num2[$direction[1]] ?? 0;
            $score3 = isset($direction[2]) && is_numeric($direction[2]) ? intval($direction[2]) : 0;
            $order = $score1 + $score2 - $score3;
            $ret[] = [
                'flow_id' => $flowId,
                'flow_name' => $direction,
                'order' => $order,
            ];
        }

        usort($ret, function($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return ($a['order'] < $b['order']) ? -1 : 1;
        });
        return $ret;
    }
}
