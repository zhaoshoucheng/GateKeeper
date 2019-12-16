<?php
/********************************************
 * # desc:    路网数据模型
 * # author:  ningxiangbing@didichuxing.com
 * # date:    2018-04-08
 ********************************************/

/**
 * Class Waymap_model
 * @property Redis_model $redis_model
 */
class Waymap_model extends CI_Model
{
    // 全局的最后一个版本
    public static $lastMapVersion = null;

    protected $token;

    protected $userid;

    protected $waymap_interface;

    /**
     * Waymap_model constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->config('nconf');
        $this->load->config('realtime_conf');
        $this->load->helper('http');

        $this->token  = $this->config->item('waymap_token');
        $this->userid = $this->config->item('waymap_userid');

        $this->getLastMapVersion();

        $this->waymap_interface = $this->config->item('waymap_interface');
    }

    public function getJunctionFilterByDistrict($cityID,$district)
    {
        $data = [
            'city_id'=>$cityID,
            'districts'=>$district,
            'version'=>self::$lastMapVersion
        ];

        $url = $this->waymap_interface . '/signal-map/mapJunction/polygon';

        $result = $this->get($url, $data);

        return array_keys($result['filter_juncs']);
    }


    /**
     * 获取最新的路网版本
     *
     * @return int
     * @throws \Exception
     */
    public function getLastMapVersion()
    {
        if (self::$lastMapVersion != null) {
            return self::$lastMapVersion;
        }

        $mapVersions = $this->getAllMapVersion();

        self::$lastMapVersion = max($mapVersions);

        return self::$lastMapVersion;
    }

    /**
     * 获取全部路网版本
     *
     * @return array
     * @throws \Exception
     */
    public function getAllMapVersion()
    {
        $data = [];

        $url = $this->config->item('waymap_interface') . '/signal-map/map/versions';

        return $this->get($url, $data);
    }

    /**
     * 根据日期获取路网版本
     *
     * @return array
     * @throws \Exception
     */
    public function getDateVersion($dates) {
        $data = [
                    'date' => implode(',', $dates),
                    'token'     => $this->config->item('waymap_token'),
                    'user_id'   => $this->config->item('waymap_userid'),
                ];
        $res = httpPOST($this->config->item('waymap_interface') . '/signal-map/map/getDateVersion', $data);
        $res = json_decode($res, true);
        if (isset($res['data'])) {
            return $res['data'];
        }
        return [];
    }

    /**
     * 路网数据接口统一调用 Get
     *
     * @param string $url     请求的地址
     * @param array  $data    请求的参数
     * @param int    $timeout 超时时间
     * @param array  $header  自定义请求头部信息
     *
     * @return array
     * @throws \Exception
     */
    public function get($url, $data, $timeout = 10000, $header = [])
    {
        $data['token']   = $this->token;
        $data['user_id'] = $this->userid;

        $res = httpGET($url, $data, $timeout, $header);
        if (!$res) {
        	com_log_warning('waymap_api_error', ERR_REQUEST_WAYMAP_API, "waymap错误", compact("url", "data", "header", "timeout", "res"));
            throw new \Exception('路网数据获取失败', ERR_REQUEST_WAYMAP_API);
        }

        $res = json_decode($res, true);
        if (!$res) {
        	com_log_warning('waymap_api_error', ERR_REQUEST_WAYMAP_API, "waymap错误", compact("url", "data", "header", "timeout", "res"));
            throw new \Exception('路网数据格式错误', ERR_REQUEST_WAYMAP_API);
        }

        if (isset($res['errno']) && $res['errno'] != 0) {
        	com_log_warning('waymap_api_error', ERR_REQUEST_WAYMAP_API, "waymap错误", compact("url", "data", "header", "timeout", "res"));
            throw new \Exception($res['errmsg'], $res['errno']);
        }

        if (isset($res['errorCode']) && $res['errorCode'] != 0) {
        	com_log_warning('waymap_api_error', ERR_REQUEST_WAYMAP_API, "waymap错误", compact("url", "data", "header", "timeout", "res"));
            throw new \Exception($res['errorMsg'], $res['errorCode']);
        }

        return $res['data'] ?? [];
    }

    /**
     * 根据关键词获取路口信息
     *
     * @param int    $city_id 城市ID
     * @param string $keyword 关键词
     *
     * @return array
     * @throws \Exception
     */
    public function getSuggestJunction($city_id, $keyword)
    {
        $version = self::$lastMapVersion;

        $data = compact('city_id', 'keyword', 'version');

        $url = $this->waymap_interface . '/signal-map/mapJunction/suggest';

        return $this->get($url, $data);
    }

    /**
     * 获取行政区信息
     *
     * @param int $city_id 城市ID
     *
     * @return array
     * @throws \Exception
     */
    public function getDistrictInfo($city_id)
    {
        $data = compact('city_id');

        $url = $this->waymap_interface . '/signal-map/city/districts';

        return $this->get($url, $data);
    }

    /**
     * 根据路口ID串获取路口名称
     *
     * @param $logic_ids
     *
     * @return array
     * @throws \Exception
     */
    public function getJunctionInfo($logic_ids)
    {
        $version = self::$lastMapVersion;
        $this->load->model('redis_model');
        $redis_key = 'getJunctionInfo_' . $version . '_' . md5($logic_ids);
        $withCache = false;
        $result = $this->redis_model->getData($redis_key);
        if (empty($result)) {
            $data = compact('logic_ids', 'version');
            $url = $this->waymap_interface . '/signal-map/map/many';
            $res = $this->post($url, $data);
            if($withCache){
                $this->redis_model->setEx($redis_key, json_encode($res), 30);
            }
            return $res;
        }
        return json_decode($result, true);
    }

    /**
     * 获取路口各相位lng、lat
     *
     * @param int    $version           版本号
     * @param string $logic_junction_id 路口ID
     * @param array  $logic_flow_ids    相位ID
     *
     * @return array
     * @throws \Exception
     */
    public function getJunctionFlowLngLat($version, $logic_junction_id, $logic_flow_ids)
    {
        $data = compact('version', 'logic_junction_id', 'logic_flow_ids');

        $url = $this->waymap_interface . '/signal-map/MapFlow/simplifyFlows';

        return $this->get($url, $data);
    }

    /**
     * 获取路口中心点坐标
     *
     * @param string $logic_id 路口ID
     *
     * @return array
     * @throws \Exception
     */
    public function getJunctionCenterCoords($logic_id)
    {
        $data = compact('logic_id');

        $url = $this->waymap_interface . '/signal-map/map/detail';

        $result = $this->get($url, $data);

        return [
            'lng' => $result['lng'],
            'lat' => $result['lat'],
        ];
    }

    /**
     * 获取全城路口
     *
     * @param int $city_id   城市ID
     * @param int $version   版本号
     * @param int $force     强制
     * @return array
     *
     * @throws \Exception
     */
    public function getAllCityJunctions($city_id, $version = 0, $force=0)
    {
        /*-------------------------------------------------
        | 先去redis中获取，如没有再调用api获取且将结果放入redis中 |
        --------------------------------------------------*/
        $this->load->model('redis_model');
        if ($version == 0) {
            $version = self::$lastMapVersion;
        }

        $result = [];

        $redis_key = 'all_city_junctions_' . $city_id . '_' . $version;
        $result = $this->redis_model->getData($redis_key);

        if($force){
            $result = 0;
        }
        if (empty($result)) {
            $offset = 0;
            $count  = 20000;

            $data = compact('offset', 'count', 'version', 'city_id');

            $url = $this->waymap_interface . '/signal-map/map/getList';

            $res = $this->get($url, $data);

            $this->redis_model->setEx($redis_key, json_encode($res), 6 * 3600);

            return $res;
        }

        return json_decode($result, true);
    }

    /**
     * 按行政区域获取城市路口
     *
     * @param int $city_id   城市ID
     * @param int $districts 行政区域ID
     * @param int $version   版本号
     * @return array
     * @throws \Exception
     */
    public function getCityJunctionsByDistricts($city_id, $districts = 0, $version = 0)
    {
        $this->load->model('redis_model');
        if ($version == 0) {
            $version = self::$lastMapVersion;
        }

        $result = [];

        $redis_key = 'districts_junctions_' . $city_id . '_' . $districts . '_' . $version . '}';
        $result = $this->redis_model->getData($redis_key);

        if (!$result) {

            $offset = 0;
            $count  = 20000;

            $data = compact('offset', 'count', 'version', 'city_id', 'districts');

            $url = $this->waymap_interface . '/signal-map/map/getList';

            $res = $this->get($url, $data);

            $this->redis_model->setEx($redis_key, json_encode($res), 6 * 3600);

            return $res;
        }

        return json_decode($result, true);
    }

    /**
     * 获取多个links的geo数据
     *
     * @param string $link_ids
     * @param int    $version
     *
     * @return array
     * @throws \Exception
     */
    public function getLinksGeoInfos($link_ids, $version, $cached=true)
    {
        $this->load->model('redis_model');
        $link_ids = is_array($link_ids) ? implode(",", $link_ids) : $link_ids;

        $redis_key = 'getLinksGeoInfos_cache_' . $version . '_' . md5($link_ids);
        // echo $redis_key;exit;
        $redisResult = $cached ? $this->redis_model->getData($redis_key) : [];
        if (!$redisResult) {
            // print_r($redisResult);exit;
            $data = compact('link_ids', 'version');
            $url = $this->waymap_interface . '/signal-map/mapFlow/linkInfo';
            $res = $this->get($url, $data);
            $linksInfo = !empty($res['links_info']) ? $res['links_info'] : [];
            $features = [];
            foreach ($linksInfo as $linkId => $linkInfo) {
                $geomArr = !empty($linkInfo['geom']) ? explode(';', $linkInfo['geom']) : [];

                $coords = [];

                foreach ($geomArr as $geo) {

                    $geoInfo = explode(',', $geo);

                    $coords[] = [(float)$geoInfo[0], (float)$geoInfo[1]];
                }

                $linkInfo['s_node']['lng']     = $linkInfo['s_node']['lng'] ?? 0;
                $linkInfo['s_node']['lat']     = $linkInfo['s_node']['lat'] ?? 0;
                $linkInfo['s_node']['node_id'] = $linkInfo['s_node']['node_id'] ?? 0;
                $linkInfo['e_node']['lng']     = $linkInfo['e_node']['lng'] ?? 0;
                $linkInfo['e_node']['lat']     = $linkInfo['e_node']['lat'] ?? 0;
                $linkInfo['e_node']['node_id'] = $linkInfo['e_node']['node_id'] ?? 0;

                $sPoint = [
                    'geometry' => [
                        'coordinates' => [$linkInfo['s_node']['lng'] / 100000, $linkInfo['s_node']['lat'] / 100000,],
                        'type' => 'Point',
                    ],
                    'properties' => [
                        'id' => (int)$linkInfo['s_node']['node_id'],
                    ],
                    'type' => 'Feature',
                ];

                $ePoint = [
                    'geometry' => [
                        'coordinates' => [$linkInfo['e_node']['lng'] / 100000, $linkInfo['e_node']['lat'] / 100000,],
                        'type' => 'Point',
                    ],
                    'properties' => [
                        'id' => (int)$linkInfo['e_node']['node_id'],
                    ],
                    'type' => 'Feature',
                ];

                $lineString = [
                    'geometry' => [
                        'coordinates' => $coords,
                        'type' => 'LineString',
                    ],
                    'properties' => [
                        'id' => $linkId,
                        'snodeid' => (int)$linkInfo['s_node']['node_id'],
                        'enodeid' => (int)$linkInfo['e_node']['node_id'],
                    ],
                    'type' => 'Feature',
                ];
                $features[] = $sPoint;
                $features[] = $ePoint;
                $features[] = $lineString;
            }
            $res = [
                'features' => $features,
                'type' => 'FeatureCollection',
            ];
            if($cached){
                $this->redis_model->setEx($redis_key, json_encode($res), 3600);
            }
            return $res;
        }
        // print_r($redisResult);exit;
        return json_decode($redisResult, true);
    }

    /**
     * 获取某一个路口与相邻路口的links
     * http://wiki.intra.xiaojukeji.com/pages/viewpage.action?pageId=146798263
     *
     * @param     $map_version
     * @param int $city_id
     * @param     $selected_junctionid
     * @param     $selected_path
     *
     * @return array
     * @throws \Exception
     */
    public function getConnectionAdjJunctions($map_version, $city_id, $selected_junctionid, $selected_path)
    {
//        $map_version = self::$lastMapVersion;
        $data = compact('city_id', 'selected_junctionid', 'selected_path', 'map_version');

        $url = $this->waymap_interface . '/signal-map/connect/adj_junctions';

        return $this->post($url, $data, 5000, 'json');
    }

    /**
     * 路网数据接口统一调用 Post
     *
     * @param string $url         请求的地址
     * @param array  $data        请求的参数
     * @param int    $timeout     超时时间
     * @param string $contentType 参数类型
     * @param array  $header      自定义请求头部
     *
     * @return array
     * @throws \Exception
     */
    public function post($url, $data, $timeout = 10000, $contentType = 'x-www-form-urlencoded', $header = [])
    {
        $query['token']   = $this->token;
        $query['user_id'] = $this->userid;

        $url = $url . '?' . http_build_query($query);

        $data = array_merge($data, $query);

        $res = httpPOST($url, $data, $timeout, $contentType, $header);
        if (!$res) {
        	com_log_warning('waymap_api_error', ERR_REQUEST_WAYMAP_API, "waymap错误", compact("url", "data", "header", "timeout", "res"));
            throw new \Exception($url . '接口请求失败', ERR_REQUEST_WAYMAP_API);
        }

        $res = json_decode($res, true);
        if (!$res) {
        	com_log_warning('waymap_api_error', ERR_REQUEST_WAYMAP_API, "waymap错误", compact("url", "data", "header", "timeout", "res"));
            throw new \Exception('接口请求失败', ERR_REQUEST_WAYMAP_API);
        }

        if (isset($res['errno']) && $res['errno'] != 0) {
        	com_log_warning('waymap_api_error', ERR_REQUEST_WAYMAP_API, "waymap错误", compact("url", "data", "header", "timeout", "res"));
            throw new \Exception($res['errmsg'], $res['errno']);
        }
        if (isset($res['errorCode']) && $res['errorCode'] != 0) {
            if(strpos($res['errorMsg'],"logic flow is empty")===false){
            	com_log_warning('waymap_api_error', ERR_REQUEST_WAYMAP_API, "waymap错误", compact("url", "data", "header", "timeout", "res"));
                throw new \Exception($res['errorMsg'], $res['errorCode']);
            }
        }
        return $res['data'] ?? [];
    }

    /**
     * 获取指定路口结合的干线路径
     *
     * @param int   $city_id
     * @param int   $map_version
     * @param array $selected_junctionids
     *
     * @return array
     * @throws \Exception
     */
    public function getConnectPath($city_id, $map_version=0, array $selected_junctionids)
    {
        if($map_version==0){
            $map_version = self::$lastMapVersion;
        }

        $force_reverse_connect = 0;

        $data = compact('city_id', 'map_version', 'selected_junctionids', 'force_reverse_connect');

        $url = $this->waymap_interface . '/signal-map/connect/path';

        return $this->post($url, $data, 0, 'json');
    }

    /**
     * 获取路口相位信息
     *
     * @param $logic_junction_ids
     * @param $cached 是否缓存数据
     *
     * @return array
     * @throws \Exception
     */
    public function getFlowsInfo($logic_junction_ids,$cached=false)
    {
        if(empty($logic_junction_ids)){
            return [];
        }
        $this->load->helper('phase');

        $this->load->model('redis_model');
        $version = self::$lastMapVersion;

        $redis_key = 'getFlowsInfo_cache_' . $version . '_' . md5($logic_junction_ids);
        //$cached = false;    //todo 这里防止redis爆掉不写入缓存了
        $result = $cached ? $this->redis_model->getData($redis_key) : [];
        if (!$result) {
            $data = compact('logic_junction_ids', 'version');

            $url = $this->waymap_interface . '/signal-map/mapJunction/phase32';
            $res = $this->post($url, $data);
            $res = array_map(function ($v) {
                return array_column($v, 'phase_name', 'logic_flow_id');
            }, $res);

            // 调用相位接口出错
            if (count($logic_junction_ids) > 0 && count($res) == 0) {
                return [];
                com_log_warning('mapJunction_phase_empty', 0, "mapJunction_phase_empty",
                    ["junctionIds" => $logic_junction_ids, "res" => count($res),]);
            }

            if($cached){
                $this->redis_model->setEx($redis_key, json_encode($res), 30);
            }
            return $res;
        }
        return json_decode($result, true);
    }

    public function getFlowInfo32($logicJunctionId)
    {
        $flowInfos = $this->getFlowsInfo32($logicJunctionId);
        return $flowInfos[$logicJunctionId];
    }


    /**
     * getFlowsInfo32
     *
     * @param with_hidden 是否包含隐藏flow
     *
     */
    public function getFlowsInfo32($logic_junction_ids,$version=0,$with_hidden=0)
    {
        $this->load->helper('phase');
        if(empty($version)){
            $version = self::$lastMapVersion;
        }
        if (is_array($logic_junction_ids)) {
            $logic_junction_ids = implode(",", $logic_junction_ids);
        }

        $data = compact('logic_junction_ids','version','with_hidden');
        $url = $this->waymap_interface . '/signal-map/mapJunction/phase32';
        $res = $this->post($url, $data);
        // 调用相位接口出错

        if (count($res) == 0) {
            com_log_warning('mapJunction_phase_empty', 0, "mapJunction_phase_empty",
                ["junctionIds" => $logic_junction_ids, "res" => count($res),]);
        }
        $res["version"] = $version;
        return $res;
    }

    /**
     * 修改路口的flow，校准 phase_id 和 phase_name
     *
     * @param $flows
     *
     * @return array
     */
    private function adjustPhase($flows)
    {
        foreach ($flows as $key => $flow) {
            $phaseId                   = phase_map($flow['in_degree'], $flow['out_degree']);
            $phaseName                 = phase_name($phaseId);
            $flows[$key]['phase_id']   = $phaseId;
            $flows[$key]['phase_name'] = $phaseName;
        }
        return $flows;
    }

    /**
     * 获取路口相位信息
     *
     * @param $city_id
     * @param $logic_junction_id
     * @param $logic_flow_id
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function getFlowMovement($city_id, $logic_junction_id, $logic_flow_id="all", $juncMovements = 0, $version="")
    {
        if($version == ""){
            $version = $this->getLastMapVersion();
        }
        $data = compact('city_id', 'logic_junction_id', 'logic_flow_id', 'version');
        $url   = $this->waymap_interface . '/signal-map/flow/movement';
        $res = $this->get($url, $data);
        if ($juncMovements == 1) {
            return $res['juncMovements'] ?? [];
        }
        return $res['movement'] ?? [];
    }

    /**
     * 获取最近的逻辑路口
     * @param $city_id
     * @param $lng
     * @param $lat
     * @return array
     */
    public function nearestJuncByCoordinate($city_id, $lng, $lat)
    {
        $data = compact('city_id', 'lng', 'lat');

        $url   = $this->waymap_interface . '/signal-map/map/nearestJuncByCoordinate';
        return $this->get($url, $data);
    }


    /**
     * 获取路口详情 经纬度、所属城市、行政区、某某交汇口等
     *
     * @param      $logic_junction_id
     * @param      $city_id
     * @param null $map_version
     *
     * @return array
     * @throws \Exception
     */
    public function gitJunctionDetail($logic_junction_id, $city_id, $map_version = null)
    {
        $map_version = $map_version ?? self::$lastMapVersion;

        if(empty($map_version)){
            $map_version = self::$lastMapVersion;
        }

        $logic_junction_ids = $logic_junction_id;

        $data = compact('city_id', 'logic_junction_ids', 'map_version');

        $url = $this->waymap_interface . '/signal-map/mapJunction/detail';

        return $this->get($url, $data);
    }

    // 单个路口详情
    public function getJunctionDetail($logic_junction_id)
    {
        $data = [
                    'logic_id'   => $logic_junction_id,
                    // 'token'     => $this->config->item('waymap_token'),
                    // 'user_id'   => $this->config->item('waymap_userid'),
                ];


        $url   = $this->waymap_interface . '/signal-map/map/detail';
        $res = $this->get($url, $data);
        return $res;
    }

    // 单个路口flag version
    public function getJunctionVersion($logic_junction_id)
    {
        $data = [
                    'logic_junction_ids'   => $logic_junction_id,
                    // 'token'     => $this->config->item('waymap_token'),
                    // 'user_id'   => $this->config->item('waymap_userid'),
                ];

        $url   = $this->waymap_interface . '/signal-map/map/getFlagVersions';
        $res = $this->get($url, $data);
        return $res[$logic_junction_id]['default_flag_version'] ?? '';
    }

    // 单个路口main node id
    public function getLogicMaps(array $logic_ids, $version)
    {
        $data = [
            'logic_junction_ids' => implode(",", $logic_ids),
            'version' => $version,
            // 'token' => $this->config->item('waymap_token'),
            // 'user_id'   => $this->config->item('waymap_userid'),
        ];

        $url   = $this->waymap_interface . '/signal-map/map/getLogicMaps';
        $res = $this->get($url, $data);
        if (count($res) != 1) {
            return '';
        }
        $res = $res[0];
        return $res['simple_main_node_id'] ?? '';
    }

    public function flowsByJunction($logic_junction_id, $version = 0)
    {
        if($version==0){
            $version = self::$lastMapVersion;
        }
        $data = [
            'logic_junction_id'   => $logic_junction_id,
            'version' => $version,
            // 'token'     => $this->config->item('waymap_token'),
            // 'user_id'   => $this->config->item('waymap_userid'),
        ];

        $url   = $this->waymap_interface . '/signal-map/mapFlow/flowsByJunction';
        $res = $this->get($url, $data);
        return $res;
    }

    public function getRestrictJunctionCached($cityId, $version = 0, $force=0)
    {
        /*-------------------------------------------------
        | 先去redis中获取，如没有再调用api获取且将结果放入redis中 |
        --------------------------------------------------*/
        $this->load->model('redis_model');
        if ($version == 0) {
            $version = self::$lastMapVersion;
        }

        $result = [];
        $redis_key = 'all_restrict_junctions_' . $cityId . '_' . $version;
        $result = $this->redis_model->getData($redis_key);
        if($force){
            $result = 0;
        }
        if (empty($result)) {
            $res = $this->getRestrictJunction($cityId);
            $this->redis_model->setEx($redis_key, json_encode($res), 6 * 3600);
            return $res;
        }
        return json_decode($result, true);
    }

    // 获取后台区域限制的路口
    // 如果返回空数组，则代表不做限制
    public function getRestrictJunction($cityId)
    {
        $url   = $this->waymap_interface . '/signal-map/mapJunction/area';
        $ret = $this->get($url, [
            'city_id' => $cityId,
        ]);
        $junctionIds = $ret['filter_junc_ids'];
        return $junctionIds;
    }

    // 修改路口名称
    public function saveJunctionName($junctionID,$junctionName)
    {
        $url = $this->waymap_interface . '/signal-map/map/saveJunctionName';
        $ret = $this->post($url, [
            'logic_id' => $junctionID,
            'name' => $junctionName,
        ]);
        if($ret["name"]==$junctionName){
            return true;
        }
        return false;
    }


    // 修改相位信息
    public function saveFlowInfo($junctionID,$flowID,$phaseName,$isHidden=0)
    {
        $url = $this->waymap_interface . '/signal-map/mapFlow/saveDesc';
        $data = [
            'logic_junction_id' => $junctionID,
            'flows' => [
                ["logic_flow_id"=>$flowID,"desc"=>$phaseName,"is_hidden"=>$isHidden,]
            ],
        ];
        $ret = $this->post($url, $data, 5000, 'json');
        return false;
    }
}
