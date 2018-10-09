<?php
/**********************************************
* 基础类
* user:ningxiangbing@didichuxing.com
* date:2018-03-01
**********************************************/

include_once "Inroute_Controller.php";
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

        $this->load->config('white');
        $escapeSso = $this->config->item('white_escape_sso');

        if (!in_array($host, $escapeSso)) {
            $this->is_check_login = 1;

            $this->load->model('user/user', 'user');
            $this->routerUri = $this->uri->ruri_string();
            com_log_notice('_com_sign', ['ip' => $_SERVER["REMOTE_ADDR"], 'ip' => $this->input->get_request_header('X-Real-Ip')]);
            // 此处采用appid+appkey的验证
            if (isset($_REQUEST['app_id'])) {
                com_log_notice('_com_sign', ['uri' => $this->routerUri, 'request' => $_REQUEST]);
                if (!$this->_checkAuthorizedApp()) {
                    $this->_output();
                    exit();
                }
            } elseif (isset($_REQUEST['token'])
                and in_array($_REQUEST['token'], ["aedadf3e3795b933db2883bd02f31e1d", "d4971d281aee77720a00a5795bb38f85"])) {
                if (in_array(strtolower($this->uri->ruri_string()), ['task/updatetaskrate', 'task/updatetaskstatus', 'overview/verifytoken', 'task/areaflowprocess', 'task/mapversioncb'])
                    and in_array($host, ['100.69.238.11:8000'])) {
                    return;
                } else {
                    exit();
                }
                // token and whitelist ip server01, web00, web01, collector03, shuhao*3
                // in_array($this->input->get_request_header('X-Real-Ip'), ['100.90.164.31', '100.90.163.51', '100.90.163.52', '10.93.94.36', '100.90.165.26', '10.89.236.26', '10.86.108.35'])
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
        $this->load->config('nconf');

        //============>降级开始
        //判断是否符合降级规则,是则直接输出
        //避免后面资源报错问题
        $this->load->model('Downgrade_model');
        $params = $this->input->get();
        $params = array_merge($params, $this->input->post());
        $route = $this->router->class."/".$this->router->method;

        //提前准备好content
        global $cacheContent;
        if($this->Downgrade_model->isCacheUrl($route, $_SERVER['REQUEST_METHOD'],$params)){
            $cacheContent = $this->Downgrade_model->getUrlCache($route, $_SERVER['REQUEST_METHOD'], $params);
        }

        //输出降级内容
        $downgradeCityId = isset($params['city_id']) ? intval($params['city_id']) : 0;
        if($this->Downgrade_model->isOpen($downgradeCityId) && !empty($cacheContent)){
            header("Content-Type:application/json;charset=UTF-8");
            echo $cacheContent;
            exit;
        }
        //<============降级结束
    }

    // 判断当前登录用户与当前任务创建用户关系及是否可以看反推配时
    protected function setTimingType(){
        try{
            $this->load->model('junction_model');
            $back_timing_roll = $this->config->item('back_timing_roll');
            $taskId = $this->input->get_post('task_id', true);
            $taskUser = $this->junction_model->getTaskUser($taskId);
            if (in_array($taskUser, $back_timing_roll, true)) {
                $this->timingType = 2;
            }
        }catch (\Exception $e){
            com_log_warning('my_controller_set_timingtype_error', 0, $e->getMessage(), compact("taskId"));
        }
    }

    public function response($data, $errno = 0, $errmsg = '') {
        $this->output_data = $data;
        $this->errno = $errno;
        $this->errmsg = $errmsg;
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
                'data' => $this->output_data,
                'traceid' => get_traceid(),
                'username' => $this->username,
            );
            header("Content-Type:application/json;charset=UTF-8");
            echo json_encode($output);
        }
    }

    private function _checkAuthorizedApp() {
        if($this->is_check_login == 0){
            return true;
        }

        $client_sign = isset($_REQUEST['sign']) ? $_REQUEST['sign'] : "";
        $app_id = $_REQUEST['app_id'];
        $this->load->config('appkey', true);
        $app_config = $this->config->item('authirized_apps', 'appkey');
        com_log_notice('_com_sign', ['client_sign' => $client_sign, 'app_id' => $app_id, 'app_config' => $app_config]);
        if (!isset($app_config[$app_id]) || !isset($app_config[$app_id]['secret'])) {
            $this->errno = ERR_AUTH_KEY;
            $this->errmsg = "该appid:{$app_id}没有授权";
            return false;
        }
        $method = isset($app_config[$app_id]['method']) ? $app_config[$app_id]['method'] : "";

        // 如果是any获取所有参数包含get
        if($method=="any"){
            $params = array_merge($this->input->post(), $this->input->get());
        }else{
            $params = $this->input->post();
        }
        com_log_notice('_com_sign', ['params' => $params]);
        unset($params['sign']);
        print_r($method);exit;
        print_r($params);exit;
        if (!isset($params['ts'])) {
            $params['ts'] = time();
        }
        // 带时间戳的sign的时效时间为1s
        if (abs(time() - $params['ts']) > 3) {
            $this->errno = ERR_AUTH_KEY;
            $this->errmsg = "该签名已经过时";
            return false;
        }
        echo abs(time() - $params['ts']);exit;
        print_r($params);exit;

        ksort($params);
        $query_str = http_build_query($params);

        if (isset($app_config[$app_id]['white_ips']) && in_array($_SERVER['REMOTE_ADDR'],$app_config[$app_id]['white_ips'])) {
            return true;
        }

        $app_key = $app_config[$app_id]['secret'];
        $open_api = isset($app_config[$app_id]['open_api']) ? $app_config[$app_id]['open_api'] : array();
        $server_sign = substr(md5($query_str . "&" . $app_key), 7, 16);
        echo $query_str . "&" . $app_key;
        echo "<br/>";
        echo $server_sign;
        echo "<br/>";
        echo $client_sign;
        echo "<br/>";
        exit;
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
            //if($area == $val['taxi_id']){ // sso
            if ($area == $val['taxiId']){
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
