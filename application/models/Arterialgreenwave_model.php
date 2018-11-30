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
    private $greenWaveExecutingKey = 'ArterialGreenWaveExecutingKeyList';

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
        // 获取正在执行干线绿波优化的tokein集合
        $this->load->model('redis_model');
        $tokenList = $this->redis_model->smembers($this->greenWaveExecutingKey);

        /*
         判断$data['token']是否存在于redis中
         如果存在 说明已调用算法组thrift接口进行优化，直接到redis中查数据即可。
         如果不存在 说明是第一次调用，需要调用算法组thrift接口，并将此次token添加到redis中以记录已调用优化接口
        */
        // token存在redis中
        if (in_array($data['token'], $tokenList, true)) {
            // 获取redis中的数据
            $res = $this->redis_model->getData($data['token']);
            // 算法组已经将优化结通过回调函数已经存到redis中，返回前端结果并清除reids
            if (!empty($res)) {
                // 删除优化结果
                $this->redis_model->deleteData($data['token']);
                // 删除已优化标记
                $this->redis_model->sremData($this->greenWaveExecutingKey, $data['token']);

                $res = json_decode($res, true);
            }
        } else { // token不在redis中，说明第一次调用

            // 调用算法组thrift接口
            $serive = new Arterialgreenwave_vendor();
            $res = $serive->getGreenWaveOptPlan($data);
            $res = (array)$res;

            if (isset($res['errno']) && $res['errno'] == 0) {
                // 将此次token添加到redis中，以做已调用优化标记
                $this->redis_model->sadd($this->greenWaveExecutingKey, $data['token']);
            }
        }

    	if (empty($res)) {
    		return [];
    	}

        if ($res['errno'] != 0 || empty($res['opt_junction_list'])) {
            return [];
        }

        foreach ($res['opt_junction_list'] as &$v) {
            $v = (array)$v;
            foreach ($v['forward_green'] as &$forward_green) {
                $forward_green = (array)$forward_green;
            }

            foreach ($v['reverse_green'] as &$reverse_green) {
                $reverse_green = (array)$reverse_green;
            }
        }

        $result = [
            'dataList' => $res['opt_junction_list']
        ];

        return $result;
    }
}
