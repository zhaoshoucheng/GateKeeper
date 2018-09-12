<?php
/********************************************
 * # desc:    自适应区域模型
 * # author:  ningxiangbing@didichuxing.com
 * # date:    2018-07-25
 ********************************************/

class Timingadaptationarea_model extends CI_Model
{
    private $signal_mis_interface = '';

    public function __construct()
    {
        parent::__construct();

        // signal-mis 接口域名
        $this->signal_mis_interface = $this->config->item('signal_mis_interface');

        $this->load->model('redis_model');
    }

    /**
     * 获取区域列表
     * @param $data['city_id']    interger Y 城市ID
     * @return array
     */
    public function getAreaList($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = ['errno' => -1, 'errmsg' => '未知错误', 'data' =>[]];

        // 调用signal-mis接口
        try {
            $url = $this->signal_mis_interface . '/TimingAdaptation/getAreaList';

            $res = httpPOST($url, $data);
            if (empty($res)) {
                return [];
            }
            $res = json_decode($res, true);
            if ($res['errorCode'] != 0) {
                $result['errmsg'] = $res['errorMsg'];
                return $result;
            }

            // 处理数据
            $result['errno'] = 0;
            $result['data'] = $this->formatGetAreaListData($data['city_id'], $res['data']);

            return $result;
        } catch (Exception $e) {
            com_log_warning('_signal-mis_getAreaList_failed', 0, $e->getMessage(), compact("url","data","res"));
            $result['errmsg'] = '调用signal-mis的getAreaList接口出错！';
            return $result;
        }
    }

    /**
     * 格式化区域列表数据
     *    需要组织每个区域的平均延误数据、平均速度数据、
     *    平均延误与上一次对比情况、平均速度与上一次数据对比情况（0无变化、1上升、2下降）
     * @param $cityId interger Y 城市ID
     * @param $data   array    Y 数据源
     * data = array
     * (
     *      [0] => Array
     *          (
     *              [id] => 10
     *              [name] => 大明湖区域
     *              [city_id] => 12
     *              [status] => 0
     *              [llng] => 116.936191
     *              [llat] => 36.703935
     *              [rlng] => 117.076954
     *              [rlat] => 36.634540
     *              [adaptive] => 1
     *              [is_upload] => 0
     *              [junction_num] => 147
     *              [adaptive_num] => 0
     *          )
     *  )
     * @return array
     */
    private function formatGetAreaListData($cityId, $data)
    {
        if (empty($data) || (int)$city_id < 1) {
            return [];
        }

        // 存放区域平均速度与平均延误数据的redis key 前缀
        $areaRedisKey = 'area_spped_delay_data_' . $cityId;
        /**
         * 获取redis中平均速度平均延误数据
         * 存放的是json格式，json_decode后的格式为：
         * $redisData = [
         *      'xxxx(area_id为键)' => [
         *          'speed'      => xxx,
         *          'stop_delay' => xxx,
         *      ],
         *      ......
         * ]
         */
        $redisData = $this->redis_model->getData($areaRedisKey);
        if (!empty($redisData)) {
            $redisData = json_decode($redisData, true);
        }

        foreach ($data as $k=>$v) {
            // 平均速度
            $speed = 0;
            // 平均延误
            $stop_delay = 0;

            // 平均速度变化趋势 默认无变化
            $speed_trend = 0;
            // 平均延误变化趋势 默认无变化
            $stop_delay_trend = 0;

            // 获取每个区域的路口ID串
            $junctions = $this->getAreaJunctionsIds($city_id, $v['id']);
            if (!empty($junctions['data'])) {
                // 调用数据组接口 参数 路口id串 暂时没有接口，先随机
                $speed = rand(1000, 10000000);
                $stop_delay = rand(1000, 10000000);

                /**
                 * 获取上一次的平均延误、平均速度数据
                 * 与本次数据进行对比，得出是否有变化
                 * 变化定义：0无变化、1上升、2下降
                 * 所有区域比较完成后最后更新redis
                 */
                if (!empty($redisData)) {
                    $oldSpeed = $redisData[$v['id']]['speed'] ?? 0;
                    $oldStopDelay = $redisData[$v['id']]['stop_delay'] ?? 0;

                    // 对平均速度变化趋势进行判断
                    if ($speed > $oldSpeed) {
                        $speed_trend = 1; // 上升
                    } else if ($speed < $oldSpeed) {
                        $speed_trend = 2; // 下降
                    } else {
                        $speed_trend = 0; // 无变化
                    }

                    // 对平均延误变化趋势进行判断
                    if ($stop_delay > $oldStopDelay) {
                        $stop_delay_trend = 1; // 上升
                    } else if ($stop_delay < $oldStopDelay) {
                        $stop_delay_trend = 2; // 下降
                    } else {
                        $stop_delay_trend = 0; // 无变化
                    }

                    // 更新redis中平均速度与平均延误
                    $redisData[$v['id']]['speed'] = $speed;
                    $redisData[$v['id']]['stop_delay'] = $stop_delay;
                }
            }

            $data[$k]['speed'] = $speed;
            $data[$k]['stop_delay'] = $stop_delay;
            $data[$k]['speed_trend'] = $speed_trend;
            $data[$k]['stop_delay_trend'] = $stop_delay_trend;
        }

        // 更新平均速度、平均延误数据的redis
        $this->redis_model->setEx($areaRedisKey, json_encode($redisData), 24*3600);

        return $data;
    }

    /**
     * 获取区域路口信息
     * @param cityId interger Y 城市ID
     * @param areaId interger Y 区域ID
     * @return array
     */
    private function getAreaJunctionsIds($cityId, $areaId)
    {
        $url = $this->signal_mis_interface . '/getAreaJunctionList';
        $data = [
            'city_id' => $cityId,
            'area_id' => $areaId,
        ];

        $result = ['errno'=>-1, 'errmsg'=>'', 'data'=>[]];

        try {
            $junctions = httpPOST($url, $data);
            if (empty($junctions)) {
                return [];
            }
            $junctions = json_decode($junctions, true);
            if ($junctions['errorCode'] != 0) {
                $result['errmsg'] = $junctions['errorMsg'];
                return $result;
            }

            $result['errno'] = 0;
            $result['data'] = $junctions['data'] ?? [];
            return $result;
        } catch (Exception $e) {
            com_log_warning('_signal-mis_getAreaJunctionList_failed', 0, $e->getMessage(), compact("url","data","junctions"));
            $result['errmsg'] = '调用signal-mis的getAreaJunctionList接口出错！';
            return $result;
        }
    }
}
