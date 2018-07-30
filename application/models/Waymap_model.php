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
    * @param onlyIdName             是否只需要 ID NAME boolean
    * @return array
    */
    public function getJunctionInfo($ids, $onlyIdName = false)
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

            if(!$onlyIdName) {
                return $res['data'];
            } else {
                $result = [];
                foreach ($res['data'] as $datum) {
                    $result[$datum['logic_junction_id']] = $datum['name'];
                }
                return $result;
            }

        } catch (Exception $e) {
            return [];
        }
    }

    /**
    * 获取路口各相位lng、lat
    * @param $data['version']           路网版本
    * @param $data['logic_junction_id'] 逻辑路口ID
    * @param $data['logic_flow_ids']    相位id集合
    * @return array
    */
    public function getJunctionFlowLngLat($data)
    {
        if (empty($data)) {
            return [];
        }

        $data['token'] = $this->token;
        $data['user_id'] = $this->userid;

        $map_data = [];

        try {
            $map_data = httpGET($this->config->item('waymap_interface') . '/signal-map/MapFlow/simplifyFlows', $data);
            if (!$map_data) {
                // 日志
                return [];
            }
            $map_data = json_decode($map_data, true);
            if ($map_data['errorCode'] != 0 || empty($map_data['data'])) {
                return [];
            }
        } catch (Exception $e) {
            // 日志
            return [];
        }

        return $map_data;
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
     * @param $version       N 地图版本
    * @return array
    */
    public function getAllCityJunctions($city_id, $version=0)
    {
        if ((int)$city_id < 1) {
            return false;
        }

        /*---------------------------------------------------
        | 先去redis中获取，如没有再调用api获取且将结果放入redis中 |
        -----------------------------------------------------*/
        $this->load->model('redis_model');
        $redis_key = "all_city_junctions_{$city_id}_{$version}";

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
            if(!empty($version)){
                $data["version"] = $version;
            }
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
        $qArr = [
            'link_ids' => implode(",", $linksArr),
            'version'  => $mapVersion,
            'token'    => $this->config->item('waymap_token'),
            'user_id'  => $this->config->item('waymap_userid'),
        ];
        try {
            $url = $this->config->item('waymap_interface') . '/signal-map/mapFlow/linkInfo';
            $res = httpGET($url, $qArr);
            $retArr = json_decode($res, true);
            if (isset($retArr['errorCode']) && $retArr['errorCode'] == 0 && !empty($retArr['data'])) {
                $linksInfo = !empty($retArr['data']['links_info']) ? $retArr['data']['links_info'] : [];
                $features = [];
                foreach ($linksInfo as $linkId=>$linkInfo){
                    $geomArr = !empty($linkInfo['geom']) ? explode(';',$linkInfo['geom']) : [];
                    $coords = [];
                    foreach ($geomArr as $geo){
                        $geoInfo = explode(',',$geo);
                        $coords[] = [(float)$geoInfo[0],(float)$geoInfo[1],];
                    }

                    $linkInfo['s_node']['lng'] = $linkInfo['s_node']['lng'] ?? 0;
                    $linkInfo['s_node']['lat'] = $linkInfo['s_node']['lat'] ?? 0;
                    $linkInfo['s_node']['node_id'] = $linkInfo['s_node']['node_id'] ?? 0;
                    $linkInfo['e_node']['lng'] = $linkInfo['e_node']['lng'] ?? 0;
                    $linkInfo['e_node']['lat'] = $linkInfo['e_node']['lat'] ?? 0;
                    $linkInfo['e_node']['node_id'] = $linkInfo['e_node']['node_id'] ?? 0;
                    $sPoint = [
                        'geometry'=>[
                            'coordinates'=>[$linkInfo['s_node']['lng']/100000,$linkInfo['s_node']['lat']/100000,],
                            'type'=>'Point',
                        ],
                        'properties'=>[
                            'id'=>(int)$linkInfo['s_node']['node_id'],
                        ],
                        'type'=>'Feature',
                    ];
                    $ePoint = [
                        'geometry'=>[
                            'coordinates'=>[$linkInfo['e_node']['lng']/100000,$linkInfo['e_node']['lat']/100000,],
                            'type'=>'Point',
                        ],
                        'properties'=>[
                            'id'=>(int)$linkInfo['e_node']['node_id'],
                        ],
                        'type'=>'Feature',
                    ];
                    $lineString = [
                        'geometry'=>[
                            'coordinates'=>$coords,
                            'type'=>'LineString',
                        ],
                        'properties'=>[
                            'id'=>$linkId,
                            'snodeid'=>(int)$linkInfo['s_node']['node_id'],
                            'enodeid'=>(int)$linkInfo['e_node']['node_id'],
                        ],
                        'type'=>'Feature',
                    ];
                    $features[] = $sPoint;
                    $features[] = $ePoint;
                    $features[] = $lineString;
                }
                return ['features'=>$features, 'type'=>'FeatureCollection'];
            }else{
                $errorMsg = !empty($retArr['errorMsg']) ? $retArr['errorMsg'] : "The getLinksGeoInfos error format.";
                throw new \Exception($errorMsg);
            }
        } catch (Exception $e) {
            com_log_warning('_itstool_waymap_getLinksGeoInfos_error', 0, $e->getMessage(), compact("url","qArr","res"));
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 获取某一个路口与相邻路口的links
     * http://wiki.intra.xiaojukeji.com/pages/viewpage.action?pageId=146798263
     *
     * @param $qArr array 请求参数
     * @return array
     */
    public function getConnectionAdjJunctions($qArr)
    {
        if (!is_array($qArr) || empty($qArr)) {
            throw new \Exception("The qjson empty.");
        }

        try {
            $getQuery = [
                'token'    => $this->config->item('waymap_token'),
                'user_id'  => $this->config->item('waymap_userid'),
            ];
            $url = $this->config->item('waymap_interface') . '/signal-map/connect/adj_junctions';
            $url = $url."?".http_build_query($getQuery);

            $res = httpPOST($url, $qArr, 2000, 'json');
            $retArr = json_decode($res, true);
            if (isset($retArr['errorCode'])
                && $retArr['errorCode'] == 0
                && !empty($retArr['data'])) {
                return $retArr['data'];
            }else{
                $errorMsg = !empty($retArr['errorMsg']) ? $retArr['errorMsg'] : "The adj_junctions error format.";
                com_log_warning('_itstool_waymap_getConnectionAdjJunctions_errorcode', 0, $errorMsg, compact("url","qArr","res"));
                return [];
            }
        } catch (Exception $e) {
            com_log_warning('_itstool_waymap_getConnectionAdjJunctions_error', 0, $e->getMessage(), compact("url","qArr","res"));
            throw new \Exception($e->getMessage());
        }
    }

    public function getConnectPath($cityId,$mapVersion, array $selectedJunctionids)
    {
        if(empty($selectedJunctionids)){
            return [];
        }

        try {
            $getQuery = [
                'token'    => $this->config->item('waymap_token'),
                'user_id'  => $this->config->item('waymap_userid'),
            ];
            $url = $this->config->item('waymap_interface') . '/signal-map/connect/path';
            $url = $url."?".http_build_query($getQuery);
            $res = httpPOST($url, array(
                'city_id'=>$cityId,
                'map_version'=>$mapVersion,
                'selected_junctionids'=>$selectedJunctionids,
                'token' => $this->token,
                'user_id'=>$this->userid,
            ), 0, 'json');
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
