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
    protected $userid = '';

    public function __construct()
    {
        parent::__construct();

        $this->load->config('nconf');
        $this->load->helper('http');
        $this->token = $this->config->item('waymap_token');
        $this->userid = $this->config->item('waymap_userid');
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
        $data['user_id'] = $this->userid;

        try {
            $res = httpGET($this->config->item('waymap_interface') . '/signal-map/map/many', $data);
            if (!$res) {
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
        if (empty($data)) {
            return [];
        }

        $data['token'] = $this->token;
        $data['user_id'] = $this->userid;

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
        if (empty($data)) {
            return [];
        }

        $data['token'] = $this->token;
        $data['user_id'] = $this->userid;

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
        if ((int)$city_id < 1) {
            return false;
        }

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
                'user_id' => $this->userid,
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
        if (!is_array($dates) || empty($dates)) {
            return [];
        }

        $maxdate = max($dates);
        $maxdate = date('Y-m-d', strtotime($maxdate));

        $wdata = [
            'date'  => $maxdate,
            'token' => $this->token,
            'user_id' => $this->userid,
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

    /**
     * 获取多个links的geo数据
     *
     * @param $linksArr     array           linkId数组
     * @param $cityId       Integer         城市id
     * @param $mapVersion   Integer         地图版本
     * @param $token        String          token
     * @return array
     */
    public function getLinksGeoInfos($linksArr, $cityId, $mapVersion, $token="fabf12896e792723a1180e96c0f37093"){
        if (!is_array($linksArr) || empty($linksArr) || empty(implode(',', $linksArr))) {
            return [];
        }
        $qArr = [];
        $qArr['link_ids'] = implode(',', $linksArr);
        $qArr['version'] = $mapVersion;
        $qArr['city_id'] = 23;  //先都写23
        $qArr['token'] = $token;
        try {
            $res = httpGet('https://sts.didichuxing.com/api/signal/link/info', $qArr);
            $retArr = json_decode($res, true);
            if (isset($retArr['errno'])
                && $retArr['errno'] == 0
                && !empty($retArr['data'])) {
                return $retArr['data'];
            }else{
                $errorMsg = !empty($retArr['errorMsg']) ? $retArr['errorMsg'] : "The geo json error format.";
                throw new \Exception($errorMsg);
            }
        } catch (Exception $e) {
            com_log_warning('_itstool_waymap_getLinksGeoInfos_error', 0, $e->getMessage(), compact("qArr","res"));
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @deprecated
     * 新四获取多个links的geo数据
     *
     * @param $linksArr     array           linkId数组
     * @return array
     */
    public function getLinksGeoInfosByMap($linksArr){
        if (!is_array($linksArr) || empty($linksArr)) {
            return [];
        }
        $qArr = [];
        $qArr['id'] = implode(',', $linksArr);
        $qArr['version'] = '2017101118';
        $qArr['origin'] = 0;

        try {
            $res = httpPOST('http://100.69.187.40:8080/link_query/linkid_with_node', $qArr, 0 , 'raw');
            $retArr = json_decode($res, true);
            if (isset($retArr['features'])) {
                return $retArr;
            }else{
                $errorMsg = !empty($retArr['errorMsg']) ? $retArr['errorMsg'] : "The linkid_with_node error format.";
                throw new \Exception($errorMsg);
            }
        } catch (Exception $e) {
            com_log_warning('_itstool_waymap_getLinksGeoInfosByMap_error', 0, $e->getMessage(), compact("qArr","res"));
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 获取某一个路口与相邻路口的flow
     * http://wiki.intra.xiaojukeji.com/pages/viewpage.action?pageId=146798263
     *
     * @param $qArr array 请求参数
     * @return array
     */
    public function getConnectionAdjJunctions($qArr)
    {
        if(ENVIRONMENT == 'development'){
            $res = '{
	"errorCode": 0,
	"errorMsg": "",
	"data": {
		"junctions_info": {
			"2017030116_5546487": {
				"name": "经一路-生产路",
				"lng": "117.01519",
				"lat": "36.67656",
				"node_ids": [5546486, 5546487]
			},
			"2017030116_5552628": {
				"name": "经一路-白鹤",
				"lng": "117.02644",
				"lat": "36.67869",
				"node_ids": [5552627, 5552628]
			},
			"b26c6af6c3edefe326ce77ab3457515f": {
				"name": "经一路-北关北路",
				"lng": "117.01868",
				"lat": "36.67813",
				"node_ids": [10935735, 10935736]
			}
		},
		"start_junc_id": "b26c6af6c3edefe326ce77ab3457515f",
		"adj_junc_paths": [{
			"end_junc_id": "2017030116_5546487",
			"links": "128503250,73771671,73771661,704904111,704904101,204355251",
			"reverse_links": "74488990,73771691,73771681,128503271"
		}, {
			"end_junc_id": "2017030116_5552628",
			"links": "128503261,74374111,74488971,74547200",
			"reverse_links": "74309850,74430830,74374130,128503240"
		}]
	}
}';
            $retArr = json_decode($res, true);
            return $retArr['data'];
        }

        if (!is_array($qArr) || empty($qArr)) {
            throw new \Exception("The qjson empty.");
        }

        try {
            $res = httpPOST($this->config->item('waymap_interface') . '/signal-map/connect/adj_junctions', $qArr, 0, 'json');
            $retArr = json_decode($res, true);
            if (isset($retArr['errorCode'])
                && $retArr['errorCode'] == 0
                && !empty($retArr['data'])) {
                return $retArr['data'];
            }else{
                $errorMsg = !empty($retArr['errorMsg']) ? $retArr['errorMsg'] : "The adj_junctions error format.";
                throw new \Exception($errorMsg);
            }
        } catch (Exception $e) {
            com_log_warning('_itstool_waymap_getConnectionAdjJunctions_error', 0, $e->getMessage(), compact("qArr","res"));
            throw new \Exception($e->getMessage());
        }
    }


}
