<?php
/********************************************
 * # desc:    轨迹数据模型
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-11-27
 ********************************************/

/**
 * Class Traj_model
 */
class Traj_model extends CI_Model
{
    // 全局的最后一个版本
    public static $lastMapVersion = null;

    protected $token;

    protected $userid;

    protected $interface;

    /**
     * Traj_model constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->config('nconf');
        $this->load->helper('http');

        $this->token = $this->config->item('traj_token');
        $this->userid = $this->config->item('traj_userid');
        $this->interface = $this->config->item('traj_interface');
        $this->its_interface = $this->config->item('its_traj_interface');
    }

    /**
     * 获取时段划分方案
     *
     * @param array $data 请求的参数
     * @return array
     * @throws \Exception
     */
    public function getTodOptimizePlan($data)
    {
        $timeSplit = explode("-",$data['task_time_range']);
        $sstr = $timeSplit[0];
        if(strlen($sstr)==4){
            $sstr = "0".$sstr;
        }
        $estr = $timeSplit[1];
        if(strlen($estr)==4){
            $estr = "0".$estr;
        }
        $data['start_time'] =$sstr.":00";
        $data['end_time']=$estr.":00";
        $url = $this->interface . '/timeframeoptimize/getTodOptimizePlan';
        return $this->post($url, $data, 20000, "json");
    }

    /**
     * 获取干线协调时空图
     *
     * @param array $data 请求的参数
     * @return array
     * @throws \Exception
     */
    public function getSpaceTimeDiagram($data)
    {
        $url = $this->interface . '/Arterialspacetimediagram/getSpaceTimeDiagram';
        return $this->post($url, $data, 20000, "json");
    }

    /**
     * 从db并发获取pi数据
     *
     * @param array $data 请求的参数
     * @return array
     * @throws \Exception
     */
    public function getJunctionsPiConcurr($data)
    {
        $url = $this->its_interface . '/Report/queryJuncsPI';
        return $this->post($url, $data, 20000, "json");
    }

    /**
     * 获取干线协调时空图
     *
     * @param array $data 请求的参数
     * @return array
     * @throws \Exception
     */
    public function getClockShiftCorrect($data)
    {
        $url = $this->interface . '/Arterialspacetimediagram/getClockShiftCorrect';
        return $this->postRaw($url, $data, 20000, "json");
    }

    /**
     * 获取实时干线协调时空图
     *
     * @param array $data 请求的参数
     * @return array
     * @throws \Exception
     */
    public function getRealtimeClockShiftCorrect($data)
    {
        $url = $this->interface . '/Arterialspacetimediagram/getRealtimeClockShiftCorrect';
        return $this->postRaw($url, $data, 20000, "json");
    }

    /**
     * 请求绿波图优化接口
     *
     * @param array $data 请求的参数
     * @return array
     * @throws \Exception
     */
    public function queryGreenWaveOptPlan($data)
    {
        $url = $this->interface . '/Arterialgreenwave/queryGreenWaveOptPlan';
        return $this->postRaw($url, $data, 15000, "json");
    }

    /**
     * 轮询获取绿波图优化接口数据
     *
     * @param array $data 请求的参数
     * @return array
     * @throws \Exception
     */
    public function getGreenWaveOptPlan($data)
    {
        $url = $this->interface . '/Arterialgreenwave/getGreenWaveOptPlan';
        return $this->post($url, $data, 20000, "json");
    }

    /**
     * 单点绿信比优化
     *
     * @param array $data 请求的参数
     * @return array
     * @throws \Exception
     */
    public function getSplitOptimizePlan($data)
    {
        $url = $this->interface . '/greensplit/greenSplitOpt';
        return $this->post($url, $data, 20000, "json");
    }

    /**
     * 填充绿波图优化接口数据
     *
     * @param array $data 请求的参数
     * @return array
     * @throws \Exception
     */
    public function fillData($data)
    {
        $url = $this->interface . '/Arterialgreenwave/fillData';
        return $this->post($url, $data, 20000, "json");
    }

    /**
     * 轨迹接口统一调用 Post
     *
     * @param string $url 请求的地址
     * @param array $data 请求的参数
     * @param int $timeout 超时时间
     *
     * @return array
     * @throws \Exception
     */
    public function postRaw($url, $data, $timeout = 20000, $contentType='x-www-form-urlencoded')
    {
        $res = httpPOST($url, $data, $timeout, 'raw');
        if (!$res) {
            throw new \Exception('traj数据获取失败', ERR_REQUEST_WAYMAP_API);
        }
        $res = json_decode($res, true);
        if (!$res) {
            throw new \Exception('traj轨迹数据格式错误', ERR_REQUEST_WAYMAP_API);
        }

        if ($res['errorCode'] != 0) {
            throw new \Exception($res['errorMsg'], $res['errorCode']);
        }

        return $res['data'] ?? [];
    }

    /**
     * 路网数据接口统一调用 Get
     *
     * @param string $url 请求的地址
     * @param array $data 请求的参数
     * @param int $timeout 超时时间
     * @param array $header 自定义请求头部信息
     *
     * @return array
     * @throws \Exception
     */
    public function post($url, $data, $timeout = 20000, $contentType='x-www-form-urlencoded', $header = [])
    {
//        $data['token'] = $this->token;
//        $data['user_id'] = $this->userid;
        $res = httpPOST($url, $data, $timeout, $contentType, $header);

        if (!$res) {
            throw new \Exception('traj数据获取失败', ERR_REQUEST_WAYMAP_API);
        }

        $res = json_decode($res, true);

        if (!$res) {
            throw new \Exception('traj数据格式错误', ERR_REQUEST_WAYMAP_API);
        }

        if ($res['errorCode'] != 0) {
            throw new \Exception($res['errorMsg'], $res['errorCode']);
        }

        return $res['data'] ?? [];
    }

    /**
     * 路网数据接口统一调用 Get
     *
     * @param string $url 请求的地址
     * @param array $data 请求的参数
     * @param int $timeout 超时时间
     * @param array $header 自定义请求头部信息
     *
     * @return array
     * @throws \Exception
     */
    public function get($url, $data, $timeout = 20000, $header = [])
    {
        $data['token'] = $this->token;
        $data['user_id'] = $this->userid;

        $res = httpGET($url, $data, $timeout, $header);

        if (!$res) {
            throw new \Exception('路网数据获取失败', ERR_REQUEST_WAYMAP_API);
        }

        $res = json_decode($res, true);

        if (!$res) {
            throw new \Exception('路网数据格式错误', ERR_REQUEST_WAYMAP_API);
        }

        if ($res['errorCode'] != 0) {
            throw new \Exception($res['errorMsg'], $res['errorCode']);
        }

        return $res['data'] ?? [];
    }
}