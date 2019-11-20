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

        $result = $this->waymap_model->get($url, $data);

        return $result;
    }

    //提取快速路路段信息
    public function getQuickRoadSegments($cityID){
        $data = [
            'city_id'=>$cityID,
        ];

        $url = $this->waymap_interface . '/signal-map/quickroad/segments';

        $result = $this->waymap_model->post($url, $data,0,'json');

        return $result;
    }







}