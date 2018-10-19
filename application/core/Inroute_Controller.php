<?php
/**********************************************
* 基础类
* user:ningxiangbing@didichuxing.com
* date:2018-03-01
**********************************************/

class Inroute_Controller extends CI_Controller {

    public function __construct(){
        parent::__construct();
    }

    /**
     * @param $params
     * @throws Exception
     */
    protected function authToken($params){
        $this->load->config('nconf');
        $inroute = $this->config->item('inroute');
        $defaultInroute = $inroute['default'];
        $allow_host_ip = $defaultInroute['allow_host_ip'];
        $salt_token = $defaultInroute['salt_token'];
        $remote_ip = $_SERVER["REMOTE_ADDR"];

        //优先验证签名==>其次验证白名单
        if(!empty($params['current_time']) && !empty($params['sig'])){
            if(!checkSign($params,$salt_token)){
                throw new \Exception('no_permission_checkSign');
            }
            if(time()-$params['current_time']>3600){
                throw new \Exception('no_permission_expired');
            }
        }else{
            if (!empty($allow_host_ip) && !in_array($remote_ip, $allow_host_ip)) {
                throw new \Exception('no_permission_allow_host');
            }
        }
    }
}
