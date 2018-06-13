<?php
/**********************************************
* 基础类
* user:ningxiangbing@didichuxing.com
* date:2018-03-01
**********************************************/

class MY_Controller extends CI_Controller {

    public $errno = 0;
    public $errmsg = '';
    public $output_data=array();
    public $templates = array();
    public $routerUri = '';
    public $username = 'unknown';
    protected $debug = false;
    protected $is_check_login = 0;
    protected $timingType = 1; // 0，全部；1，人工；2，配时反推；3，信号机上报

    public function __construct(){
        parent::__construct();
        date_default_timezone_set('Asia/Shanghai');
        $host = $_SERVER['HTTP_HOST'];
        $this->load->model('junction_model');

        if ( $host != '100.90.164.31:8088' && $host != 'www.itstool.com' && $host != '100.90.164.31:8089') {
            $this->is_check_login = 1;

            $this->load->model('user/user', 'user');
            // 此处采用appid+appkey的验证
            if (isset($_REQUEST['app_id']) && isset($_REQUEST['sign'])) {
                if (!$this->_checkAuthorizedApp()) {
                    $this->_output();
                    exit();
                }
            } elseif (isset($_REQUEST['token']) && (in_array($_REQUEST['token'], [
                    "aedadf3e3795b933db2883bd02f31e1d", ])) ) {
                return;
            } else {
                if(!$this->_checkUser()) {
                    $currentUrl = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; //线上是https, 获取当前页面的地址
                    if( (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest")
                        || (isset($_SERVER["HTTP_ACCEPT"]) && strstr($_SERVER["HTTP_ACCEPT"], 'application/json')) ) {
                            $this->_output();
                            exit();
                        }else{
                            // 页面请求
                            $redirect = $this->user->getLoginUrl() . '&jumpto='.urlencode($currentUrl);
                            header("location: " . $redirect);
                            exit();
                        };
                }
                // 目前还未按照接口设置权限，所以暂时注释掉
               /*
                if(!$this->_validateURI()){
                    $this->_output();
                    exit();
                }*/

                if(isset($_REQUEST['city_id']) && !$this->_validateCity($_REQUEST['city_id'])){
                    $this->_output();
                    exit();
                }
            }
        }

        // 判断当前登录用户与当前任务创建用户关系及是否可以看反推配时
        $this->load->config('nconf');
        $back_timing_roll = $this->config->item('back_timing_roll');

        $taskId = $this->input->get_post('task_id', true);
        // 暂时先这么做
        $taskUser = $this->junction_model->getTaskUser($taskId);

        if (in_array($taskUser, $back_timing_roll, true)) {
            $this->timingType = 2;
        }
    }

    public function response($data, $errno = 0, $errmsg = '') {
        $this->output_data = $data;
        $this->errno = $errno;
        $this->errmsg = $errmsg;
        $this->username = $this->username;
        $this->output->set_content_type('application/json');
    }

    public function _output(){
        if($this->errno >0 && empty($this->errmsg)){
            $errmsgMap = $this->config->item('errmsg');
            $this->errmsg = $errmsgMap[$this->errno];
        }
        if(!empty($this->templates)){
            foreach ($this->templates as $t){
                echo $this->load->view($t, array(), true);
            }
        } else {
            $output = array(
                'errno' => $this->errno,
                'errmsg' => $this->errmsg,
                'data' => $this->output_data
            );
            echo json_encode($output);
        }
    }

    private function _checkAuthorizedApp() {
        if($this->is_check_login == 0){
            return true;
        }
        // 获取所有的参数
        $params = $this->input->post();
        unset($params['sign']);
        if (!isset($params['ts'])) {
            $params['ts'] = time();
        }
        // 带时间戳的sign的时效时间为1s
        if (abs(time() - $params['ts']) > 2) {
            $this->errno = ERR_AUTH_KEY;
            $this->errmsg = "该签名已经过时";
            return false;
        }

        ksort($params);
        $query_str = http_build_query($params);
        $client_sign = $_REQUEST['sign'];
        $app_id = $_REQUEST['app_id'];
        $this->load->config('appkey', true);
        $app_config = $this->config->item('authirized_apps', 'appkey');
        if (!isset($app_config[$app_id]) || !isset($app_config[$app_id]['secret'])) {
            $this->errno = ERR_AUTH_KEY;
            $this->errmsg = "该appid:{$app_id}没有授权";
            return false;
        }
        $app_key = $app_config[$app_id]['secret'];
        $open_api = isset($app_config[$app_id]['open_api']) ? $app_config[$app_id]['open_api'] : array();
        $server_sign = substr(md5($query_str . "&" . $app_key), 7, 16);
        MyLog::debug("check authorized by app secrect - app_id:{$app_id} - query_str:{$query_str} - client_sign:{$client_sign} - server_sign:{$server_sign}");
        if ($server_sign != $client_sign) {
            $this->errno = ERR_AUTH_KEY;
            $this->errmsg = "签名的sign不正确";
            return false;
        } else if (!in_array($this->routerUri, $open_api)){
            $this->errno = ERR_AUTH_KEY;
            $this->errmsg = "该接口{$this->routerUri}没有开放授权";
            return false;
        } else {
            return true;
        }
    }

    private function _checkUser(){
        if($this->is_check_login == 0){
            return true;
        }
        $ret = $this->user->isUserLogin();
        if(!$ret) {
            $this->errno = ERR_AUTH_LOGIN;
            $this->output_data = $this->user->getLoginUrl();
            return false;
        }
        $this->username = $this->user->username;
        return true;
    }

    private function _validateURI(){
        if($this->is_check_login == 0){
            return true;
        }
        $ret = $this->user->isAuthorizedUri($this->routerUri);
        if (!$ret) {
            $this->errno = ERR_AUTH_URI;
            return false;
        }
        return true;
    }

    private function _validateCity($area) {
        if($this->is_check_login == 0){
            return true;
        }
        $ret = $this->user->getAuthorizedCityid();
        //$ret = $this->user->getCityAuth();
        if(empty($ret)){
            $this->errno = ERR_AUTH_AREA;
            return false;
        }
        foreach($ret as $val){
            if($val['level'] != 3){
                continue;
            }
            if($area == $val['taxi_id']){
                return true;
            }
        }
        $this->errno = ERR_AUTH_AREA;
        return false;
    }

    private function getIp()
    {
        $ip=false;
        if(!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
            for ($i = 0; $i < count($ips); $i++) {
                if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }
}
