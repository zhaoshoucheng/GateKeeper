<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use \Services\PermissionService;

class Permission extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('permission/menu_conf');
        $this->load->config('backend/userauth');
    }

    public function getMenuList()
    {
        $menu = $this->config->item('menu');
        $data = $menu['menuList'][$menu['check']($this->username)] ?? [];
        $this->response($data);
    }

    public function getMenuListNew()
    {
        $menu = $this->config->item('menu');
        $menuType = $menu['check']($this->username);
        if($menuType!=1){
            $data = $menu['menuList'][$menuType];
        }else{
            $service = new PermissionService();
            $data = $service->getUserMenus();
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