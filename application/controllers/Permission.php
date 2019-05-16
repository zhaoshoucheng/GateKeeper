<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use \Services\PermissionService;

class Permission extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('permission/menu_conf');
    }

    public function getMenuListOld()
    {
        $menu = $this->config->item('menu');
        $data = $menu['menuList'][$menu['check']($this->username)] ?? [];
        $this->response($data);
    }

    public function getMenuList()
    {
        $menu = $this->config->item('menu');
        $menuType = $menu['check']($this->username);
        if($menuType!=1){
            $data = $menu['menuList'][$menuType];
        }else{
            $service = new PermissionService();
            $data = $service->getUserMenus();
            //无权限时读取配置文件
            if(empty($data)){
                $data = $menu['menuList'][1];
            }
        }
        $this->response($data);
    }

    public function getPermissionList()
    {
        $service = new PermissionService();
        $result = $service->getUserPermissions();
        $this->response($result);
    }
}