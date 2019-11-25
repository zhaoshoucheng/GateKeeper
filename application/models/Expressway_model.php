<?php
/**
 * 快速路相关
 * User: zhuyewei
 * Date: 2019/11/18
 * Time: 下午3:23
 */

class Expressway_model extends CI_Model
{
    /**
     * @var CI_DB_query_builder
     */
    protected $db;

    // 全局的最后一个版本
    public static $lastMapVersion = null;

    protected $token;

    protected $userid;

    protected $waymap_interface;

    /**
     * Feedback_model constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->config('nconf');
        $this->token  = $this->config->item('waymap_token');
        $this->userid = $this->config->item('waymap_userid');
        $this->load->model('waymap_model');

        $this->getLastMapVersion();

        $this->waymap_interface = $this->config->item('waymap_interface');

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

        return $this->waymap_model->get($url, $data);
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

    //提取快速路的movement
    public function getQuickRoadMovement($cityID)
    {
        $data = [
            'city_id'=>$cityID,
            'version'=>self::$lastMapVersion
        ];

        $url = $this->waymap_interface . '/signal-map/quickroad/movements';
        $url = 'http://10.86.108.35:8031/signal-map/quickroad/movements';

        $result = $this->waymap_model->get($url, $data);

        return $result;
    }

    public function getQuickRoadSegmentsByJunc($cityID,$juncs = []){
        $data = [
            'city_id'=>(int)$cityID,
        ];
        if(!empty($juncs)){
            $data['junction_ids'] = $juncs;
        }

        $url = $this->waymap_interface . '/signal-map/quickroad/segments';
        $url = "http://10.86.108.35:8031/signal-map/quickroad/segments";


        $result = $this->waymap_model->post($url, $data,0,'json');

        return $result;
    }


    //提取快速路路段信息
    public function getQuickRoadSegments($cityID,$names = []){
        $data = [
            'city_id'=>(int)$cityID,
        ];
        if(!empty($names)){
            $data['names'] = $names;
        }

        $url = $this->waymap_interface . '/signal-map/quickroad/segments';
        $url = "http://10.86.108.35:8031/signal-map/quickroad/segments";


        $result = $this->waymap_model->post($url, $data,0,'json');

        return $result;
    }


    //查询快速路的指标
    public function getOnlineExpresswayQuotaList($cityID,$startJuncID,$endJuncID){
        $req = [
            'city_id' => $cityID,
            'upstream_id' => $startJuncID,
            'downstream_id' => $endJuncID,
        ];
        if (!empty($userPerm['junction_id'])) {
            $req['junction_ids'] = $userPerm['junction_id'];
        }
        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/GetJunctionAlarmDataByJunctionAVG', $req, 0, 'json');
        if (!empty($res)) {
            $res = json_decode($res, true);
            return $res['data'];
        } else {
            return [];
        }
    }

    //查询快速路的指标详情
    public function getExpresswayQuotaDetail(){
        $req = [
            'city_id' => $city_id,
            'dates' => $dates,
            'hour' => $hour,
        ];
        if (!empty($userPerm['junction_id'])) {
            $req['junction_ids'] = $userPerm['junction_id'];
        }
        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/GetJunctionAlarmDataByJunctionAVG', $req, 0, 'json');
        if (!empty($res)) {
            $res = json_decode($res, true);
            return $res['data'];
        } else {
            return [];
        }
    }







}