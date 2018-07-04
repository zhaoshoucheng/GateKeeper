<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 16/12/6
 * Time: 11:35
 */

class User extends CI_Model{
    public $username = null;
    public $access_token = null;

    public function __construct()
    {
        //$this->load->helper('redis');
        //可以考虑将auth修改为sso
        $this->load->model('user/auth', 'auth');
        $this->load->model('user/upm', 'upm');
        $this->access_token = $_COOKIE['ticket'] ?? null;
        $this->username = $_COOKIE['username'] ?? null;
    }

    public function login(){
        header('Location:' . $this->auth->getLoginUrl());
        exit;
    }

    public function getLoginUrl(){
        return  $this->auth->getLoginUrl();
    }

    public function logout(){
        $this->username = 'guest';
        $this->access_token = null;
        header('Location:' . $this->auth->getLogoutUrl());
        exit;
    }

    public function isUserLogin(){
        if(empty($this->access_token)){
            return false;
        }
        $username = $this->username;
        if(!$username){
            return false;
        }
        $isLogin = $this->auth->isValidticket($this->access_token, $this->username);
        if(!$isLogin){
            return false;
        }
        return true;
    }


    //用户是否有权限
    public function isAuthorizedUri($uri) {
        if(empty($this->username) || $this->username == 'guest'){
            return false;
        }
         
        //return $this->auth->isValidFeature($this->access_token, $uri);
        return $this->upm->isValidFeature($this->username, $uri);
    }

    public function getAuthorizedCityid(){
        if(empty($this->username)){
            return array();
        }
        //$ret = $this->auth->getUserFeatureAndArea($this->access_token, $this->username);
        $ret = $this->auth->getUserAreas($this->username); 
        if(!$ret){
            return array();
        }
        return $ret['citys'];
    }

    public function getCityAuth() {
         if(empty($this->username)){
            return array();
        }
        $ret = $this->auth->getCityAuth($this->access_token, $this->username);
        if(!$ret){
            return array();
        }
        return $ret;
    }
}
