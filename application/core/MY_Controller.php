<?php
/**********************************************
 * 基础类
 * user:ningxiangbing@didichuxing.com
 * date:2018-03-01
 **********************************************/

include_once "Inroute_Controller.php";

/**
 * Class MY_Controller
 *
 * @property CI_Benchmark $benchmark
 * @property User $user
 * @property CI_URI $uri
 * @property CI_Router $router
 * @property Downgrade_model $Downgrade_model
 * @property Junction_model $junction_model
 */
class MY_Controller extends CI_Controller
{
    public $errno = 0;
    public $errmsg = '';
    public $output_data = [];
    public $templates = [];
    public $routerUri = '';
    public $username = 'unknown';
    public $userPerm = [];

    /**
     * @var CI_Output
     */
    public $output;

    protected $debug = false;
    protected $timingType = 1; // 0，全部；1，人工；2，配时反推；3，信号机上报


    public function __construct()
    {
        parent::__construct();
        $this->benchmark->mark('api_start');

        date_default_timezone_set('Asia/Shanghai');

        $host = $_SERVER['HTTP_HOST'];
        $clientIp = $_SERVER['REMOTE_ADDR'];

        $this->load->config('white');
        $escapeSso = $this->config->item('white_escape_sso');
        $escapeClient = $this->config->item('white_token_clientip_escape');

        $this->load->config('nconf');
        $this->routerUri = $this->uri->ruri_string();
        $token = isset($_REQUEST['token']) ? $_REQUEST['token'] : "";

        $accessType = 0; // 权限认证通过的类型
        $accessUser = ""; // 权限认证通过的用户信息

        // 有一些机器是不需要进行sso验证的，这里就直接跳过
        if (!in_array($host, $escapeSso) && empty($_SERVER['HTTP_DIDI_HEADER_USERGROUPKEY'])) {

            $this->load->model('user/user', 'user');

            if (isset($_REQUEST['app_id'])) {
                // 此处采用appid + appkey的验证, 开放平台
                if (!$this->_checkAuthorizedApp()) {
                    $this->_output();
                    exit();
                }

                $accessType = 1; // 使用开放平台验证通过
                $accessUser = $_REQUEST['app_id'];

            } elseif (in_array($host, ['100.69.238.11:8000'])) {
                // 通过vip进行的请求
                if (!$this->_checkInnerVipAccess($token, $this->routerUri)) {
                    $this->_output();
                    exit();
                }

                $accessType = 2; // 使用vip白名单验证通过

            } elseif (!empty($escapeClient[$clientIp]) && in_array($token,$escapeClient[$clientIp])) {
                com_log_notice('_com_sign_escape_client', ['token' => $token, 'escapeClient' => $escapeClient[$clientIp]]);
                //pass

                $accessType = 3; // 使用白名单+token验证通过
                $accessUser = $token;

            } else {
                // 检测用户
                if (!$this->_checkUser()) {
                    $currentUrl = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; //线上是https, 获取当前页面的地址
                    if ((isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest")
                        || (isset($_SERVER["HTTP_ACCEPT"]) && strstr($_SERVER["HTTP_ACCEPT"], 'application/json'))) {
                        $this->_output();
                        exit();
                    } else {
                        // 页面请求
                        $redirect = $this->user->getLoginUrl() . '&jumpto=' . urlencode($currentUrl);
                        header("location: " . $redirect);
                        exit();
                    }
                }

                // 验证城市
                $needValidateCity = $this->config->item('validate_city');
                if (isset($_REQUEST['city_id']) && $needValidateCity && !$this->_validateCity($_REQUEST['city_id'])) {
                    $this->_output();
                    exit();
                }

                $accessType = 4; // 使用用户名密码访问
                $accessUser = $this->username;
            }
        }

        com_log_notice('_com_sign', [
            'access_user' => $accessUser,
            'access_type' => $accessType,
            'ip' => $_SERVER["REMOTE_ADDR"],
            'ip2' => $this->input->get_request_header('X-Real-Ip'),
            'city_id' => isset($_REQUEST['city_id']) ? $_REQUEST['city_id'] : "",
            'uri' => $this->routerUri,
            'request' => $_REQUEST
        ]);

        //============>降级开始
        //判断是否符合降级规则,是则直接输出
        //避免后面资源报错问题
        $this->load->model('Downgrade_model');
        $params = $this->input->get();
        $params = array_merge($params, $this->input->post());
        $route  = $this->router->class . "/" . $this->router->method;

        //提前准备好content
        global $cacheContent;
        if ($this->Downgrade_model->isCacheUrl($route, $_SERVER['REQUEST_METHOD'], $params)) {
            $cacheContent = $this->Downgrade_model->getUrlCache($route, $_SERVER['REQUEST_METHOD'], $params);
        }

        //输出降级内容
        $downgradeCityId = isset($params['city_id']) ? intval($params['city_id']) : 0;
        if ($this->Downgrade_model->isOpen($downgradeCityId) && !empty($cacheContent)) {
            header("Content-Type:application/json;charset=UTF-8");
            echo $cacheContent;
            exit;
        }
        //<============降级结束

        //写入权限信息
        if(!empty($_SERVER['HTTP_DIDI_HEADER_USERGROUPKEY'])){
            $redisKey = $_SERVER['HTTP_DIDI_HEADER_USERGROUPKEY'];
            $this->load->model('Redis_model');
            $permData = $this->Redis_model->getData($redisKey);
            $this->userPerm = json_decode($permData,true);
            //获取的city_id对应权限
            $this->userPerm = !empty($this->userPerm["data"][$downgradeCityId]) ? $this->userPerm["data"][$downgradeCityId] : [];
            $this->userPerm['group_id'] = $_SERVER['HTTP_DIDI_HEADER_USERGROUP'];
            if(!empty($this->userPerm)){
                $this->userPerm['city_id'] = !empty($this->userPerm['city_id']) ? explode(";",$this->userPerm['city_id']) : [];
                $this->userPerm['area_id'] = !empty($this->userPerm['area_id']) ? explode(";",$this->userPerm['area_id']) : [];
                $this->userPerm['admin_area_id'] = !empty($this->userPerm['admin_area_id']) ? explode(";",$this->userPerm['admin_area_id']) : [];
                $this->userPerm['route_id'] = !empty($this->userPerm['route_id']) ? explode(";",$this->userPerm['route_id']) : [];
                $this->userPerm['junction_id'] = !empty($this->userPerm['junction_id']) ? explode(";",$this->userPerm['junction_id']) : [];
            }
        }
    }

    // 判断当前登录用户与当前任务创建用户关系及是否可以看反推配时

    /**
     * 签名方式修改,支持post+get混合参数
     * @return bool
     */
    private function _checkAuthorizedApp()
    {
        $paramTmp = array_merge($this->input->post(), $this->input->get());
        com_log_notice('_checkauthorizedapp_param', [
            'params' => $paramTmp,
            'url' => $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'],
        ]);

        $client_sign = isset($_REQUEST['sign']) ? $_REQUEST['sign'] : "";
        $app_id      = $_REQUEST['app_id'];

        $this->load->config('appkey', true);
        $app_config = $this->config->item('authirized_apps', 'appkey');
        com_log_notice('_com_sign_config', ['client_sign' => $client_sign, 'app_id' => $app_id, 'app_config' => $app_config]);

        if (!isset($app_config[$app_id]) || !isset($app_config[$app_id]['secret'])) {
            com_log_notice('_checkauthorizedapp_err', [
                'error' => "error_appid",
                'params' => $paramTmp,
                'url' => $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'],
            ]);
            $this->errno  = ERR_AUTH_KEY;
            $this->errmsg = "该appid:{$app_id}没有授权";
            return false;
        }

        $method  = isset($app_config[$app_id]['method']) ? $app_config[$app_id]['method'] : "";
        $timeout = isset($app_config[$app_id]['timeout']) ? $app_config[$app_id]['timeout'] : 10;

        // 如果是any获取所有参数包含get
        if ($method == "any") {
            $params = array_merge($this->input->post(), $this->input->get());
        } else {
            $params = $this->input->post();
        }
        com_log_notice('_com_sign', ['params' => $params, 'url' => '/' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']]);
        unset($params['sign']);

        if (!isset($params['ts'])) {
            $params['ts'] = time();
        }

        // 带时间戳的sign的时效时间为1s
        if (abs(time() - $params['ts']) > $timeout) {
            com_log_notice('_checkauthorizedapp_err', [
                'error' => "error_tstimeout",
                'time' => time(),
                'ts' => $params['ts'],
                'timeout' => $timeout,
                'params' => $paramTmp,
                'url' => $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'],
            ]);
            $this->errno  = ERR_AUTH_KEY;
            $this->errmsg = "该签名已经过时";
            return false;
        }
        ksort($params);
        $query_str = http_build_query($params);

        if (isset($app_config[$app_id]['white_ips']) && in_array($_SERVER['REMOTE_ADDR'], $app_config[$app_id]['white_ips'])) {
            return true;
        }

        $app_key     = $app_config[$app_id]['secret'];
        $open_api    = isset($app_config[$app_id]['open_api']) ? $app_config[$app_id]['open_api'] : [];
        $server_sign = substr(md5($query_str . "&" . $app_key), 7, 16);
        if ($server_sign != $client_sign) {
            com_log_notice('_checkauthorizedapp_err', [
                'error' => "error_sign",
                'params' => $paramTmp,
                'url' => $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'],
                'md5str' => $query_str . "&" . $app_key,
                'server_sign' => $server_sign,
                'client_sign' => $client_sign,
                'app_id' => $app_id,
            ]);
            $this->errno  = ERR_AUTH_KEY;
            $this->errmsg = "签名的sign不正确";
            return false;
        } elseif (!in_array($this->routerUri, $open_api)) {
            com_log_notice('_checkauthorizedapp_err', [
                'error' => "error_apiurl_notopen",
                'params' => $paramTmp,
                'url' => $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'],
                'md5str' => $query_str . "&" . $app_key, 'server_sign' => $server_sign,
                'app_id' => $app_id,
            ]);
            $this->errno  = ERR_AUTH_KEY;
            $this->errmsg = "该接口{$this->routerUri}没有开放授权";
            return false;
        }
        return true;
    }

    /*
     * 判断是否通过内部vip调用指定接口
     */
    private function _checkInnerVipAccess($token, $uri)
    {
        // token 必须满足条件
        if (!in_array($token, [
            "aedadf3e3795b933db2883bd02f31e1d",
            "d4971d281aee77720a00a5795bb38f85"
        ])) {
            return false;
        }

        // 请求的uri必须在某个条件内
        if (!in_array(strtolower($uri),
            [
                'task/updatetaskrate', 'task/updatetaskstatus',
                'overview/verifytoken', 'task/areaflowprocess',
                'task/mapversioncb'
            ]
        )) {
            return false;
        }

        return true;
    }

    public function _output()
    {
        $this->benchmark->mark('api_end');

        if ($this->errno > 0 && empty($this->errmsg)) {
            $errmsgMap    = $this->config->item('errmsg');
            $this->errmsg = $errmsgMap[$this->errno];
        }

        if (!empty($this->templates)) {
            foreach ($this->templates as $t) {
                echo $this->load->view($t, [], true);
            }
        } else {
            $output = [
                'errno' => $this->errno,
                'errmsg' => $this->errmsg,
                'data' => $this->output_data,
                'traceid' => get_traceid(),
                'username' => $this->username,
                'time' => [
                    'a' => $this->benchmark->elapsed_time('api_start', 'api_end') . '秒',
                    's' => $this->benchmark->elapsed_time('service_start', 'service_end') . '秒',
                ],
            ];
            header("Content-Type:application/json;charset=UTF-8");
            echo json_encode($output);
        }
    }

    /*
     * 验证用户是否通过sso
     */
    private function _checkUser()
    {
        $ret = $this->user->isUserLogin();
        if (!$ret) {
            $this->errno       = ERR_AUTH_LOGIN;
            $this->output_data = $this->user->getLoginUrl();
            return false;
        }

        $this->username = $this->user->username;

        return true;
    }

    /*
     * 验证用户是否有这个城市的权限
     */
    private function _validateCity($area)
    {
        $ret = $this->user->getAuthorizedCityid();
        if (empty($ret)) {
            $this->errno = ERR_AUTH_AREA;
            return false;
        }

        foreach ($ret as $val) {
            if ($area == $val['taxiId']) {
                return true;
            }
        }
        $this->errno = ERR_AUTH_AREA;
        return false;
    }

    public function response($data, $errno = 0, $errmsg = '')
    {
        $this->output_data = $data;
        $this->errno       = $errno;
        $this->errmsg      = $errmsg;
        $this->output->set_content_type('application/json');
    }

    protected function setTimingType()
    {
        try {
            $this->load->model('junction_model');
            $back_timing_roll = $this->config->item('back_timing_roll');
            $taskId           = $this->input->get_post('task_id', true);
            $taskUser         = $this->junction_model->getTaskUser($taskId);
            if (in_array($taskUser, $back_timing_roll, true)) {
                $this->timingType = 2;
            }
        } catch (\Exception $e) {
            com_log_warning('my_controller_set_timingtype_error', 0, $e->getMessage(), compact("taskId"));
        }
    }

    /**
     *
     * @param $rules ['city_id' => 'required|is_natural_no_zero', ... ]
     *
     * @throws Exception
     */
    protected function validate($rules)
    {
        foreach ($rules as $field => $rule) {
            $this->form_validation->set_rules($field, $field, $rule);
        }

        if ($this->form_validation->run() == false) {
            $errmsg = current($this->form_validation->error_array());
            throw new Exception($errmsg, ERR_PARAMETERS);
        }
    }

    private function _validateURI()
    {
        $ret = $this->user->isAuthorizedUri($this->routerUri);
        if (!$ret) {
            $this->errno = ERR_AUTH_URI;
            return false;
        }
        return true;
    }
}
