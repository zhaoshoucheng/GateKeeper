<?php
/**********************************************
 * 基础类
 * user:ningxiangbing@didichuxing.com
 * date:2018-03-01
 **********************************************/

include_once "Inroute_Controller.php";
include_once "AsyncTask_Controller.php";

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
    public $permCitys = [];

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
        $escapeToken = $this->config->item('white_token_escape');

        $this->load->config('nconf');
        $this->routerUri = $this->uri->ruri_string();
        $token = isset($_GET['token']) ? $_GET['token'] : "";
        if (empty($token)) {
            $token = isset($_POST['token']) ? $_POST['token'] : "";
        }

        $accessType = 0; // 权限认证通过的类型
        $accessUser = ""; // 权限认证通过的用户信息

        com_log_notice('_com_before_sso', [
            'access_user' => $accessUser,
            'access_type' => $accessType,
            'ip' => $_SERVER["REMOTE_ADDR"],
            'ip2' => $this->input->get_request_header('X-Real-Ip'),
            'ip3' => $this->input->get_request_header('X-Forwarded-For'),
            'city_id' => isset($_REQUEST['city_id']) ? $_REQUEST['city_id'] : "",
            'uri' => $this->routerUri,
            'request' => $_REQUEST
        ]);

        // 有一些机器是不需要进行sso验证的，这里就直接跳过
        if (!in_array($host, $escapeSso) && empty($_SERVER['HTTP_DIDI_HEADER_GATEWAY'])) {

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

            } elseif (in_array($token, ['02efffde3a9b5a8f8f04f7c00fb92cb0'])) {
                //diyu白名单

                //是否只现实了
                //此处设置header头信息
                $_SERVER["HTTP_DIDI_HEADER_USERGROUPKEY"] = "signal_gateway_upm_414";
                $_SERVER["HTTP_DIDI_HEADER_USERGROUP"] = "414";
                $_SERVER["HTTP_DIDI_HEADER_USERCITYPERM"] = "12000,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120,121,122,123,124,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,176,177,178,179,180,181,182,183,184,185,186,187,188,189,190,191,192,193,194,195,196,197,198,199,200,201,202,203,204,205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,230,231,232,233,234,235,236,237,238,239,240,241,242,243,244,245,246,247,248,249,250,251,252,253,254,255,256,257,258,259,260,261,262,263,264,265,266,267,268,269,270,271,272,273,274,275,276,277,278,279,280,281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,301,302,303,304,305,306,307,308,309,310,311,312,313,314,315,316,317,318,319,320,321,322,323,324,325,326,327,328,329,330,331,332,333,334,335,336,337,338,339,340,341,342,343,344,345,346,347,348,349,350,351,352,353,354,355,356,357,14840,359,360,361,362,363,364,365,366,0,0,0,0,0,0,0,0,0,0,0,0,0,0";
                $_SERVER["HTTP_DIDI_HEADER_RID"] = "645add2c5d44627b6bb86d76295a7a02";
                $_SERVER["HTTP_DIDI_HEADER_GATEWAY"] = "IDzQoSk1gVN5IU69KtZDoQ==";

                com_log_notice('_com_sign_escape_token', ['token' => $token, 'escapeClient' => $escapeClient[$clientIp]]);
                //pass
                $accessType = 5; // token验证通过
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
        } else {
            $accessUser = $_COOKIE['username'] ?? "unkown";
        }
        $this->username = $accessUser;

        //客户端ip与city_id绑定校验:联通定制版
        if($_SERVER["REMOTE_ADDR"]=="123.124.255.72"
            && isset($_REQUEST['city_id'])
            && !in_array($_REQUEST['city_id'],["1","4"])){
            $this->errno  = ERR_AUTH_KEY;
            $this->errmsg = "当前城市无权限";
            $this->_output();
            exit();
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
        $this->permCitys = [];
        // 从用户权限获取可以验证的城市列表获取可以验证的城市列表
        if (isset($_SERVER['HTTP_DIDI_HEADER_USERCITYPERM'])) {
            $citys = $_SERVER['HTTP_DIDI_HEADER_USERCITYPERM'];
            $userPermCitys = explode(",", $citys);
            foreach ($userPermCitys as $city) {
                if (intval($city) <= 0) {
                    continue;
                }
                $this->permCitys[] = $city;
            }
            com_log_notice("_com_perm_citys",[
                "citys" => $_SERVER['HTTP_DIDI_HEADER_USERCITYPERM'],
                "username" => $accessUser,
                "permCitys" => $this->permCitys,
            ]);
            if (empty($this->permCitys)) {
                $this->errno = ERR_AUTH_AREA;
                $this->_output();
                exit;
            }
        }

        if(!empty($_SERVER['HTTP_DIDI_HEADER_USERGROUPKEY'])){
            $redisKey = $_SERVER['HTTP_DIDI_HEADER_USERGROUPKEY'];
            $this->load->model('Redis_model');
            $permData = $this->Redis_model->getData($redisKey);
            $userPerm = json_decode($permData,true);
            $cityId = isset($_REQUEST['city_id']) ? $_REQUEST['city_id'] : 0;
            //获取的city_id对应权限
            if (!empty($userPerm["data"][$cityId])) {
                $this->userPerm = $userPerm["data"][$cityId];
            } else if (!empty($userPerm["data"]) && $cityId > 0) {
                $this->errno = ERR_AUTH_AREA;
                $this->errmsg = "没有此地区的数据权限";
                $this->_output();
                exit;
            } else {
                $this->userPerm = [];
            }
            if(!empty($this->userPerm)){
                $this->userPerm['group_id'] = $_SERVER['HTTP_DIDI_HEADER_USERGROUP'];
                $this->userPerm['city_id'] = !empty($this->userPerm['city_id']) ? explode(";",$this->userPerm['city_id']) : [];
                $this->userPerm['area_id'] = !empty($this->userPerm['area_id']) ? explode(";",$this->userPerm['area_id']) : [];
                $this->userPerm['admin_area_id'] = !empty($this->userPerm['admin_area_id']) ? explode(";",$this->userPerm['admin_area_id']) : [];
                $this->userPerm['route_id'] = !empty($this->userPerm['route_id']) ? explode(";",$this->userPerm['route_id']) : [];
                $this->userPerm['junction_id'] = !empty($this->userPerm['junction_id']) ? explode(";",$this->userPerm['junction_id']) : [];
            }
        }
        if (!empty($this->permCitys)) {
            $needValidateCity = $this->config->item('validate_city');
            if (isset($_REQUEST['city_id']) && $needValidateCity && !in_array($_REQUEST['city_id'], $this->permCitys)) {
                $this->errno = ERR_AUTH_AREA;
                $this->_output();
                exit();
            }
        }

        com_log_notice('_com_perm', [
            'username' => $accessUser,
            'access_type' => $accessType,
            'city_id' => isset($_REQUEST['city_id']) ? $_REQUEST['city_id'] : "",
            'user_perm' => $this->userPerm,
            'permCitys' => $this->permCitys,
        ]);
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
                'task/mapversioncb', 'task/areaflowprocess',
                'overview/verifytoken',
                'road/roadinfo',
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
            $cityId = $val['taxiId'];
            if ($area == $cityId) {
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

    protected function get_validate($rules,$data)
    {

        $this->form_validation->validation_data = $data;
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

    protected function convertJsonToPost(){
        //json格式转换为post格式
        $params = file_get_contents("php://input");
        if(!empty(json_decode($params,true))){
            $_POST = json_decode($params,true);
        }
    }
}
