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
     * http://wiki.intra.xiaojukeji.com/pages/viewpage.action?pageId=146798263
     *
     * @param $qArr array 请求参数
     * @return array
     */
    public function getLinksGeoInfos($flowArr){
        $tmpStr = '{
	"type": "FeatureCollection",
	"features": [{
		"geometry": {
			"type": "LineString",
			"coordinates": [
				[116.37703, 39.93088],
				[116.37701, 39.93148]
			]
		},
		"properties": {
			"fid": 437896,
			"geom": "{\"type\": \"LineString\", \"coordinates\": [[116.37703, 39.93088], [116.37701, 39.93148]]}",
			"mapid": "595673",
			"id": "684388",
			"kind_num": "1",
			"kind": "0601",
			"width": "55",
			"direction": "1",
			"toll": "2",
			"const_st": "1",
			"undconcrid": "",
			"snodeid": "773672",
			"enodeid": "774811",
			"funcclass": "5",
			"length": "0.067",
			"detailcity": "1",
			"through": "1",
			"unthrucrid": "",
			"ownership": "0",
			"road_cond": "1",
			"special": "0",
			"admincodel": "110102",
			"admincoder": "110102",
			"uflag": "1",
			"onewaycrid": "",
			"accesscrid": "",
			"speedclass": "6",
			"lanenums2e": "1",
			"lanenume2s": "1",
			"lanenum": "1",
			"vehcl_type": "11110001110000000000000000000000",
			"elevated": "0",
			"structure": "0",
			"usefeecrid": "",
			"usefeetype": "",
			"spdlmts2e": "400",
			"spdlmte2s": "400",
			"spdsrcs2e": "0",
			"spdsrce2s": "0",
			"dc_type": "1",
			"nopasscrid": "",
			"outbancrid": "",
			"numbancrid": "",
			"parkflag": "0",
			"origin": 1,
			"active": 1,
			"rel_active": 1,
			"edit_id": 0,
			"rl_route_id": "259224",
			"pathname": "西黄城根北街"
		},
		"type": "Feature"
	}, {
		"geometry": {
			"type": "LineString",
			"coordinates": [
				[116.37706, 39.93006],
				[116.37704, 39.93051],
				[116.37703, 39.93088]
			]
		},
		"properties": {
			"fid": 437899,
			"geom": "{\"type\": \"LineString\", \"coordinates\": [[116.37706, 39.93006], [116.37704, 39.93051], [116.37703, 39.93088]]}",
			"mapid": "595673",
			"id": "684391",
			"kind_num": "1",
			"kind": "0601",
			"width": "55",
			"direction": "1",
			"toll": "2",
			"const_st": "1",
			"undconcrid": "",
			"snodeid": "774812",
			"enodeid": "773672",
			"funcclass": "5",
			"length": "0.091",
			"detailcity": "1",
			"through": "1",
			"unthrucrid": "",
			"ownership": "0",
			"road_cond": "1",
			"special": "0",
			"admincodel": "110102",
			"admincoder": "110102",
			"uflag": "1",
			"onewaycrid": "",
			"accesscrid": "",
			"speedclass": "6",
			"lanenums2e": "1",
			"lanenume2s": "1",
			"lanenum": "1",
			"vehcl_type": "11110001110000000000000000000000",
			"elevated": "0",
			"structure": "0",
			"usefeecrid": "",
			"usefeetype": "",
			"spdlmts2e": "400",
			"spdlmte2s": "400",
			"spdsrcs2e": "0",
			"spdsrce2s": "0",
			"dc_type": "1",
			"nopasscrid": "",
			"outbancrid": "",
			"numbancrid": "",
			"parkflag": "0",
			"origin": 1,
			"active": 1,
			"rel_active": 1,
			"edit_id": 0,
			"rl_route_id": "259224",
			"pathname": "西黄城根北街"
		},
		"type": "Feature"
	}, {
		"geometry": {
			"type": "Point",
			"coordinates": [116.37703, 39.93088]
		},
		"properties": {
			"fid": 432436,
			"geom": "{\"type\": \"Point\", \"coordinates\": [116.37703, 39.93088]}",
			"mapid": "595673",
			"id": "773672",
			"kind_num": "1",
			"kind": "10ff",
			"cross_flag": "0",
			"light_flag": "0",
			"cross_lid": "0",
			"mainnodeid": "0",
			"subnodeid": "0",
			"subnodeid2": "",
			"adjoin_mid": "0",
			"adjoin_nid": "0",
			"node_lid": "684388|684391",
			"origin": 1,
			"active": 1,
			"rel_active": 1,
			"edit_id": 0
		},
		"type": "Feature"
	}, {
		"geometry": {
			"type": "Point",
			"coordinates": [116.37701, 39.93148]
		},
		"properties": {
			"fid": 433046,
			"geom": "{\"type\": \"Point\", \"coordinates\": [116.37701, 39.93148]}",
			"mapid": "595673",
			"id": "774811",
			"kind_num": "1",
			"kind": "10ff",
			"cross_flag": "0",
			"light_flag": "0",
			"cross_lid": "0",
			"mainnodeid": "0",
			"subnodeid": "0",
			"subnodeid2": "",
			"adjoin_mid": "0",
			"adjoin_nid": "0",
			"node_lid": "684388|684389",
			"origin": 1,
			"active": 1,
			"rel_active": 1,
			"edit_id": 0
		},
		"type": "Feature"
	}, {
		"geometry": {
			"type": "Point",
			"coordinates": [116.37706, 39.93006]
		},
		"properties": {
			"fid": 433047,
			"geom": "{\"type\": \"Point\", \"coordinates\": [116.37706, 39.93006]}",
			"mapid": "595673",
			"id": "774812",
			"kind_num": "1",
			"kind": "10ff",
			"cross_flag": "0",
			"light_flag": "0",
			"cross_lid": "0",
			"mainnodeid": "0",
			"subnodeid": "0",
			"subnodeid2": "",
			"adjoin_mid": "0",
			"adjoin_nid": "0",
			"node_lid": "684390|684391",
			"origin": 1,
			"active": 1,
			"rel_active": 1,
			"edit_id": 0
		},
		"type": "Feature"
	}]
}';
        $retArr = [];
        foreach ($flowArr as $flowId){
            $retArr[$flowId] = json_decode($tmpStr,true);
        }
        return$retArr;
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
        $tmp = array (
  'select_junction_id' => '2017031615_5678903',
  'connected_junction_infos' =>
  array (
    0 =>
    array (
      'connect_junction' =>
      array (
        'junction_id' => '2017031615_5678904',
        'name' => 'xxx',
        'lng' => 116.5134,
        'lat' => 28.0976,
      ),
      'segments' =>
      array (
        0 =>
        array (
          'links' => '896784320,896784310',
          'length' => 100,
          'direction' => -1,
        ),
        1 =>
        array (
          'links' => '896784311,896784321',
          'length' => 100,
          'direction' => 1,
        ),
      ),
    ),
    1 =>
    array (
      'connect_junction' =>
      array (
        'junction_id' => '2017031615_5678902',
        'name' => 'xxx',
        'lng' => 116.5004,
        'lat' => 28.0976,
      ),
      'segments' =>
      array (
        0 =>
        array (
          'links' => '896784320,896784310',
          'length' => 100,
          'direction' => -1,
        ),
        1 =>
        array (
          'links' => '896784311,896784321',
          'length' => 100,
          'direction' => 1,
        ),
      ),
    ),
  ),
);
        return $tmp;

        if (!is_array($qArr) || empty($qArr)) {
            return [];
        }
        try {
            $res = httpPOST($this->config->item('waymap_interface') . '/signal-map/connect/adj_junctions', $qArr, 0, 'json');
            $retArr = json_decode($res, true);
            if (isset($retArr['errorCode'])
                && $retArr['errorCode'] == 0
                && !empty($retArr['data'])) {
                return $retArr['data'];
            }
        } catch (Exception $e) {
            com_log_warning('_itstool_waymap_getConnectionAdjJunctions_error', 0, $e->getMessage(), compact("qArr","res"));
            return [];
        }
    }


}
