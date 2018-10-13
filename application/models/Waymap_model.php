<?php
/********************************************
# desc:    路网数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-04-08
 ********************************************/

namespace Models;

class Waymap_model extends \CI_Model
{
    protected $token;
    private $email_to = 'ningxiangbing@didichuxing.com';
    protected $userid = '';

    // 全局的最后一个版本
    public static $lastMapVersion = [];

    public function __construct()
    {
        parent::__construct();

        $this->load->config('nconf');
        $this->load->config('realtime_conf');
        $this->load->helper('http');
        $this->token = $this->config->item('waymap_token');
        $this->userid = $this->config->item('waymap_userid');
    }

    /**
     * 根据关键词获取路口信息
     * @param $city_id
     * @param $keyword
     */
    public function getSuggestJunction($city_id, $keyword)
    {
        $data['token'] = $this->token;
        $data['user_id'] = $this->userid;
        $data['city_id'] = $city_id;
        $data['keyword'] = $keyword;

        try {
            $junctions = httpGET($this->config->item('waymap_interface') . '/signal-map/mapJunction/suggest', $data);
            if (!$junctions) {
                return [];
            }

            $junctions = json_decode($junctions, true);
            if ($junctions['errorCode'] != 0 || empty($junctions['data'])) {
                return [];
            }

            return $junctions['data'];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 获取行政区信息
     * @param $city_id
     */
    public function getDistrictInfo($city_id)
    {
        $data['token'] = $this->token;
        $data['user_id'] = $this->userid;
        $data['city_id'] = $city_id;

        try {
            $ret = httpGET($this->config->item('waymap_interface') . '/signal-map/city/districts', $data);
            if (!$ret) {
                return [];
            }

            $ret = json_decode($ret, true);
            if ($ret['errorCode'] != 0 || empty($ret['data'])) {
                return [];
            }

            return $ret['data'];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 根据路口ID串获取路口名称
     * @param logic_junction_ids     逻辑路口ID串     string
     * @param returnFormat           数据返回格式      array [key => 'id', value => ['name',...]]
     * @return array
     */
    public function getJunctionInfo($ids, $returnFormat = null)
    {
        $data['token'] = $this->token;
        $data['user_id'] = $this->userid;

        // 获取最新版本
        $mapVersions = $this->getAllMapVersion();
        if (!empty($mapVersions)) {
            $data['version'] = max($mapVersions);
        }

        try {
            $ids_array = explode(',', $ids);

            $res = [];

            foreach (array_chunk($ids_array, 100) as $ids) {
                $data['logic_ids'] = implode(',', $ids);
                $result = httpGET($this->config->item('waymap_interface') . '/signal-map/map/many', $data);

                if (!$result) {
                    // 日志
                    return [];
                }
                $result = json_decode($result, true);
                if ($result['errorCode'] != 0 || !isset($result['data']) || empty($result['data'])) {
                    return [];
                }

                $res = array_merge($res, $result['data']);
            }

            if(is_null($returnFormat)) {
                return $res;
            } else {

                //检查 $returnFormat 格式
                if(!is_array($returnFormat) || !array_key_exists('key', $returnFormat)
                    || !array_key_exists('value', $returnFormat) || !is_string($returnFormat['key']))
                    return $res;

                $result = [];

                if(is_string($returnFormat['value'])) {
                    $result = array_column($res, $returnFormat['value'], $returnFormat['key']);
                } else {
                    foreach ($res as $datum) {
                        $temp = [];
                        foreach ($returnFormat['value'] as $item) {
                            $temp[$item] = $datum[$item];
                        }
                        $result[$datum[$returnFormat['key']]] = $temp;
                    }
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
                return [];
            }
        } catch (Exception $e) {
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
    public function getAllCityJunctions($city_id, $version=0, $returnFormat = null)
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
                return false;
            }
        } else {
            $city_junctions = json_decode($city_junctions, true);
        }

        if(!is_null($returnFormat)) {

            //检查 $returnFormat 格式
            if(!is_array($returnFormat) || !array_key_exists('key', $returnFormat)
                || !array_key_exists('value', $returnFormat) || !is_string($returnFormat['key']))
                return $city_junctions;

            $result = [];

            if(is_string($returnFormat['value'])) {
                $result = array_column($city_junctions, $returnFormat['value'], $returnFormat['key']);
            } else {
                foreach ($city_junctions as $datum) {
                    $temp = [];
                    foreach ($returnFormat['value'] as $item) {
                        $temp[$item] = $datum[$item];
                    }
                    $result[$datum[$returnFormat['key']]] = $temp;
                }
            }

            return $result;
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

        $wdata = [
            'date'  => implode(",",$dates),
            'token' => $this->token,
            'user_id' => $this->userid,
        ];
        $result_version = '';
        try {
            $url = $this->config->item('waymap_interface') . '/signal-map/map/getDateVersion';

            $map_version = httpPOST($url, $wdata);
            $retArr = json_decode($map_version, true);

            if (isset($retArr['errorCode'])
                && $retArr['errorCode'] == 0
                && !empty($retArr['data'])) {
                foreach ($retArr['data'] as $k=>$v) {
                    if(!empty($v)){
                        $result_version = $v;
                    }
                }
                return $result_version;
            }else{
                $errorMsg = !empty($retArr['errorMsg']) ? $retArr['errorMsg'] : "GetMapVersion error.";
                com_log_warning('_itstool_waymap_getMapVersion_errorcode', 0, $errorMsg, compact("url","wdata","map_version"));
                throw new \Exception("waymap:".$errorMsg);
            }
        } catch (Exception $e) {
            com_log_warning('_itstool_waymap_getMapVersion_error', 0, $e->getMessage(), compact("url","wdata","map_version"));
            throw new \Exception($e->getMessage());
        }
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
     * @throws Exception
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

            $res = httpPOST($url, $qArr, 5000, 'json');
            $retArr = json_decode($res, true);
            if (isset($retArr['errorCode'])
                && $retArr['errorCode'] == 0
                && !empty($retArr['data'])) {
                return $retArr['data'];
            }else{
                if(isset($retArr['errorCode'])){
                    $errorMsg = !empty($retArr['errorMsg']) ? $retArr['errorMsg'] : "The adj_junctions error format.";
                }else{
                    $errorMsg = " unknown error.";
                }
                com_log_warning('_itstool_waymap_getConnectionAdjJunctions_errorcode', 0, $errorMsg, compact("url","qArr","res"));
                throw new \Exception("waymap:".$errorMsg);
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

    /**
     * 获取路口相位信息
     */
    public function getFlowsInfo($junctionIds)
    {
        if(empty($junctionIds)) {
            return [];
        }

        try {
            $this->load->helper('phase');
            $getQuery = [
                'logic_junction_ids' => $junctionIds,
                'user_id' => $this->config->item('waymap_userid'),
                'token' => $this->config->item('waymap_token'),
                'version' => $this->getLastMapVersion(),
            ];
            $url = $this->config->item('waymap_interface') . '/signal-map/mapJunction/phase';

            $res = httpPOST($url, $getQuery);

            $res = json_decode($res, true);
            if ($res['errorCode'] != 0 || !isset($res['data']) || empty($res['data'])) {
                return [];
            }

            $res = array_map(function ($v) {
                // 纠正这里的phase_id和phase_name
                $v = $this->adjustPhase($v);
                return array_column($v, 'phase_name', 'logic_flow_id');
            }, $res['data']);

            return $res;
        } catch (Exception $e) {

            return [];
        }
    }


    /**
     * 修改路口的flow，校准phase_id和phase_name
     * @param $flows flow数据
     * @return array
     */
    private function adjustPhase($flows)
    {
        foreach ($flows as $key => $flow) {
            $phaseId = phase_map($flow['in_degree'], $flow['out_degree']);
            $phaseName = phase_name($phaseId);
            $flows[$key]['phase_id'] = $phaseId;
            $flows[$key]['phase_name'] = $phaseName;
        }
        return $flows;
    }

    /**
     * 获取路口相位信息
     */
    public function getFlowMovement($cityId, $logicJunctionId, $logicFlowId)
    {
        try {
            $query = [
                'city_id' => $cityId,
                'logic_junction_id' => $logicJunctionId,
                'logic_flow_id' => $logicFlowId,
                'user_id' => $this->config->item('waymap_userid'),
                'token' => $this->config->item('waymap_token'),
            ];
            $url = $this->config->item('waymap_interface') . '/signal-map/flow/movement';

            $res = httpGET($url, $query);

            $res = json_decode($res, true);
            if ($res['errorCode'] != 0 || !isset($res['data']) || empty($res['data'])) {
                return [];
            }

            return $res['data']['movement'];
        } catch (Exception $e) {

            return [];
        }
    }


    /*
     * 获取最新的路网版本
     */
    public function getLastMapVersion()
    {
        if (!empty(self::$lastMapVersion)) {
            return self::$lastMapVersion;
        }

        $mapVersions = $this->getAllMapVersion();
        self::$lastMapVersion = max($mapVersions);
        return self::$lastMapVersion;
    }

    /**
     * 获取全部路网版本
     */
    public function getAllMapVersion()
    {
        $data['token'] = $this->token;
        $data['user_id'] = $this->userid;

        try {
            $mapVersions = httpGET($this->config->item('waymap_interface') . '/signal-map/map/versions', $data);
            if (!$mapVersions) {
                return [];
            }

            $mapVersions = json_decode($mapVersions, true);
            if ($mapVersions['errorCode'] != 0 || empty($mapVersions['data'])) {
                return [];
            }

            return $mapVersions['data'];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 获取路口详情 经纬度、所属城市、行政区、某某交汇口等
     * @param $data['logic_junction_id'] string   Y 路口ID
     * @param $data['city_id']           interger Y 城市ID
     * @param $data['map_version']       string   N 地图版本
     * @return array
     */
    public function gitJunctionDetail($data)
    {
        if (empty($data)) {
            return ['errno' => -1, 'errmsg'=>'参数请求错误！'];
        }

        $wdata['token'] = $this->token;
        $wdata['user_id'] = $this->userid;
        $wdata['city_id'] = $data['city_id'];
        $wdata['logic_junction_ids'] = $data['logic_junction_id'];

        if (empty($data['map_version'])) {
            $allVersion = $this->getAllMapVersion();
            $wdata['map_version'] = max($allVersion);
        } else {
            $wdata['map_version'] = $data['map_version'];
        }

        try {
            $detail = httpGET($this->config->item('waymap_interface') . '/signal-map/mapJunction/detail', $wdata);
            if (!$detail) {
                return ['errno'=>-1, 'errmsg'=>'路网返回路口信息为空！'];
            }

            $detail = json_decode($detail, true);
            if ($detail['errorCode'] != 0 || empty($detail['data'])) {
                return ['errno'=>-1, 'errmsg'=>$detail['errorMsg']];
            }

            return ['errno'=>0, 'data'=>$detail['data']];
        } catch (Exception $e) {
            com_log_warning('_itstool_waymap_gitJunctionDetail_error', 0, $e->getMessage(), compact("wdata","detail"));
            return ['errno'=>-1, 'errmsg'=>'路网服务异常！'];
        }
    }
}
