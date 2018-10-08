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
        $this->load->model('redis_model');
        $this->db = $this->load->database('default', true);
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
            $result['data'] = $this->sortByNema($flowsInfo[$data['logic_junction_id']]);
        }

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
            return strpos($direction, '掉头') === False;
        });

        $scores = [
            '南左' => 8 * 5,
            '北直' => 7 * 5,
            '西左' => 6 * 5,
            '东直' => 5 * 5,
            '北左' => 4 * 5,
            '南直' => 3 * 5,
            '东左' => 2 * 5,
            '西直' => 1 * 5,
        ];



        $ret = [];
        foreach ($flowInfos as $flowId => $direction) {
            $word2 = mb_substr($direction, 0 , 2);
            $order = $scores[$word2] ?? 0;
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
            return ($a['order'] > $b['order']) ? -1 : 1;
        });
        return $ret;
    }

    /**
     * 获取最新的 hour
     * @param $cityId
     * @param null $date
     * @return false|string
     */
    public function getLastestHour($cityId, $date = null)
    {
        if(($hour = $this->redis_model->getData("its_realtime_lasthour_$cityId"))) {
            return $hour;
        }

        // 查询优化
        $sql = "SELECT `hour` FROM `real_time_{$cityId}`  WHERE 1 ORDER BY updated_at DESC,hour DESC LIMIT 1";
        $result = $this->db->query($sql)->first_row();
        if(!$result)
            return date('H:i:s');

        return $result->hour;
    }
}
