<?php
/********************************************
# desc:    路网数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-04-08
********************************************/

class Waymap_model extends CI_Model
{
    protected $token;
    private $email_to = 'ningxiangbing@didichuxing.com';

    public function __construct()
    {
        parent::__construct();

        $this->load->config('nconf');
        $this->load->helper('http');
        $this->token = $this->config->item('waymap_token');
    }

    /**
    * 根据路口ID串获取路口名称
    * @param logic_junction_ids     逻辑路口ID串     string
    * @return array
    */
    public function getJunctionInfo($ids)
    {
        $data['logic_ids'] = $ids;
        $data['token'] = $this->token;

        try {
            $res = httpGET($this->config->item('waymap_interface') . '/signal-map/map/many', $data);
            if(!$res){
                // 日志
                return [];
            }
            $res = json_decode($res, true);
            if ($res['errorCode'] != 0 || !isset($res['data']) || empty($res['data'])) {
                return [];
            }
            return $res['data'];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
    * 获取路口各相位lng、lat及
    * @param $data['version']           路网版本
    * @param $data['logic_junction_id'] 逻辑路口ID
    * @return array
    */
    public function getJunctionFlowAndCenterLngLat($data)
    {
        if (empty($data)) return [];

        $data['token'] = $this->token;

        $map_data = [];

        try {
            $map_data = httpGET($this->config->item('waymap_interface') . '/signal-map/mapFlow/AllByJunctionWithLinkAttr', $data);
            if (!$map_data) {
                // 日志
                return [];
            }
            $map_data = json_decode($map_data, true);
            if ($map_data['errorCode'] != 0 || empty($map_data['data'])) {
                // 日志
                $content = 'data = ' . json_encode($data)
                        . ' \r\n interface = '
                        . $this->config->item('waymap_interface') . '/signal-map/mapFlow/AllByJunctionWithLinkAttr'
                        . '\r\n result = ' . json_encode($map_data);
                sendMail($this->email_to, 'logs: 获取全城路口失败', $content);
                return [];
            }
        } catch (Exception $e) {
            // 日志
            $content = 'data = ' . json_encode($data)
                    . ' \r\n interface = '
                    . $this->config->item('waymap_interface') . '/signal-map/mapFlow/AllByJunctionWithLinkAttr'
                    . '\r\n result = ' . json_encode($map_data);
            sendMail($this->email_to, 'logs: 获取全城路口失败', $content);
            return [];
        }

        foreach ($map_data['data'] as $k=>$v) {
            $result[$k]['logic_flow_id'] = $v['logic_flow_id'];
            $result[$k]['lng'] = $v['inlink_info']['s_node']['lng'];
            $result[$k]['lat'] = $v['inlink_info']['s_node']['lat'];
        }

        return $result;
    }

    /**
    * 获取路口中心点坐标
    * @param $data['logic_id']  逻辑路口ID
    * @return array
    */
    public function getJunctionCenterCoords($data)
    {
        if (empty($data)) return [];

        $data['token'] = $this->token;

        try {
            $junction_info = httpGET($this->config->item('waymap_interface') . '/signal-map/map/detail', $data);
            if (!$junction_info) {
                return [];
            }

            $junction_info = json_decode($junction_info, true);
            if ($junction_info['errorCode'] != 0 || empty($junction_info['data'])) {
                $content = 'data = ' . json_encode($data)
                        . ' \r\n interface = '
                        . $this->config->item('waymap_interface') . '/signal-map/map/detail'
                        . '\r\n result = ' . json_encode($junction_info);
                sendMail($this->email_to, 'logs: 获取全城路口失败', $content);
                return [];
            }
        } catch (Exception $e) {
            $content = 'data = ' . json_encode($data)
                    . ' \r\n interface = '
                    . $this->config->item('waymap_interface') . '/signal-map/map/detail'
                    . '\r\n result = ' . json_encode($junction_info);
            sendMail($this->email_to, 'logs: 获取全城路口失败', $content);
            return [];
        }

        $result['lng'] = isset($junction_info['data']['lng']) ? $junction_info['data']['lng'] : '';
        $result['lat'] = isset($junction_info['data']['lat']) ? $junction_info['data']['lat'] : '';

        return $result;
    }

    /**
    * 获取全城路口
    * @param city_id        Y 城市ID
    * @return array
    */
    public function getAllCityJunctions($city_id)
    {
        if ((int)$city_id < 1) return false;

        /*---------------------------------------------------
        | 先去redis中获取，如没有再调用api获取且将结果放入redis中 |
        -----------------------------------------------------*/
        $this->load->model('redis_model');
        $redis_key = "all_city_junctions_{$city_id}";

        // 获取redis中数据
        $city_junctions = $this->redis_model->getData($redis_key);
        if (!$city_junctions) {
            $data = [
                'city_id' => $city_id,
                'token'   => $this->token,
                'offset'  => 0,
                'count'   => 10000
            ];
            try {
                $res = httpGET($this->config->item('waymap_interface') . '/signal-map/map/getList', $data);
                if (!$res) {
                    // 添加日志、发送邮件
                    $content = 'data = ' . json_encode($data)
                        . ' \r\ninterface = ' . $this->config->item('waymap_interface') . '/signal-map/map/getList';
                    sendMail($this->email_to, 'logs: 获取全城路口失败', $content);
                    return false;
                }
                $res = json_decode($res, true);
                if (isset($res['errorCode'])
                    && $res['errorCode'] == 0
                    && isset($res['data'])
                    && count($res['data']) >= 1) {
                    $this->redis_model->deleteData($redis_key);
                    $this->redis_model->setData($redis_key, json_encode($res['data']));
                    $this->redis_model->setExpire($redis_key, 3600 * 24);
                    $city_junctions = $res['data'];
                }
            } catch (Exception $e) {
                $content = 'data = ' . json_encode($data)
                        . ' \r\ninterface = ' . $this->config->item('waymap_interface') . '/signal-map/map/getList';
                sendMail($this->email_to, 'logs: 获取全城路口失败', $content);
                return false;
            }
        } else {
            $city_junctions = json_decode($city_junctions, true);
        }

        return $city_junctions;
    }

    /**
    * 获取最新地图版本号
    * @param $dates array 日期 ['20180102', '20180103']
    * @return array
    */
    public function getMapVersion($dates)
    {
        if (!is_array($dates) || empty($dates)) return [];

        $maxdate = max($dates);
        $maxdate = date('Y-m-d', strtotime($maxdate));

        $wdata = [
            'date'  => $maxdate,
            'token' => $this->token
        ];

        $map_version = [];
        try {
            $map_version = httpPOST($this->config->item('waymap_interface') . '/signal-map/map/getDateVersion', $wdata);
            $map_version = json_decode($map_version, true);
            if (!$map_version) return [];
        } catch (Exception $e) {
            return [];
        }
        if (!empty($map_version['data'])) {
            foreach ($map_version['data'] as $k=>$v) {
                $map_version = $v;
            }
        }
        return $map_version;

    }
}
