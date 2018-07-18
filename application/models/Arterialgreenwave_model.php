<?php
/********************************************
# desc:    干线绿波数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-06-29
********************************************/

use Didi\Cloud\ItsMap\Arterialgreenwave_vendor;

class Arterialgreenwave_model extends CI_Model
{
    private $tb = '';

    public function __construct()
    {
        parent::__construct();
    }

    /**
    * 获取干线绿波优化方案
    * @param $data['junctions']      array    Y 路口集合 如下示例：
    * [
    *  [
    *   "junction_id"=>"xx432423423",   // 路口ID
    *   "cycle"=>60,                    // 配时周期
    *   "offset"=>3,                    // 偏移量
    *   "forward_green"=>[              // 正向绿灯信息 如只取反向时传-1:forward_green['green_start'=>-1, green_duration=>-1]
    *       [
    *           'green_start' => 0,     // 绿灯开始时间
    *           'green_duration' => 10  // 绿灯持续时间
    *       ],
    *       ......
    *   ],
    *   "reverse_green"=>[              // 反向绿灯信息 如只取正向时传-1:reverse_green['green_start'=>-1, green_duration=>-1]
    *       [
    *           'green_start' => 0,     // 绿灯开始时间
    *           'green_duration' => 10  // 绿灯持续时间
    *       ],
    *       ......
    *   ],
    *   "lock_cycle"=>1,                // 周期是否锁定 0：否 1：是
    *   "lock_offset"=>0                // 偏移量是否锁定 0：否 1：是
    *   ],
    * ]
    * @param $data['forward_length'] array    N 正向路段长度  格式：[100, 200, 300] 只取反向时可不传
    * @param $data['forward_speed']  array    N 正向速度     格式：[100, 200, 300] 只取反向时可不传
    * @param $data['reverse_length'] array    N 反向路段长度 格式：[100, 200, 300]  只取正向时可不传
    * @param $data['reverse_speed']  array    N 返向速度     格式：[100, 200, 300] 只取正向时可不传
    * @param $data['method']         interger Y 0=>正向 1=>反向 2=>双向
    * @param $data['token']          string   Y 此次请求唯一标识，用于前端轮询
    * @return array
    */
    public function getGreenWaveOptPlan($data)
    {

    	$serive = new Arterialgreenwave_vendor();
    	$res = $serive->getGreenWaveOptPlan($data);
    	if (empty($res)) {
    		return [];
    	}

        $res = (array)$res;
        if ($res['errno'] != 0 || empty($res['opt_junction_list'])) {
            return [];
        }

        foreach ($res['opt_junction_list'] as &$v) {
            $v = (array)$v;
            foreach ($v['forward_green'] as &$vv) {
                $vv = (array)$vv;
            }

            foreach ($v['reverse_green'] as &$vv) {
                $vv = (array)$vv;
            }
        }

        $result = [
            'dataList' => $res['opt_junction_list'],
            'token'    => $data['token']
        ];

        return $result;
    }
}
