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
        $this->load->model('user/auth', 'auth');
        $this->access_token = $_COOKIE['ticket'] ?? null;
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
        $isLogin = $this->auth->isValidticket($this->access_token);
        if(!$isLogin){
            return false;
        }
        $username = $this->auth->getUsername($this->access_token);
        if(!$username){
            return false;
        }
        $this->username = $username;
        return true;
    }


    //用户是否有权限
    public function isAuthorizedUri($uri){
        if(empty($this->username) || $this->username == 'guest'){
            return false;
        }
        return $this->auth->isValidFeature($this->access_token, $uri);
    }

    public function getAuthorizedCityid(){
        if(empty($this->username)){
            return array();
        }
        $ret = $this->auth->getUserFeatureAndArea($this->access_token, $this->username);
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
