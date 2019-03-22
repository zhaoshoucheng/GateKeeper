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

    public function getMenuList()
    {
        $menu = $this->config->item('menu');

        $data = $menu['menuList'][$menu['check']($this->username)] ?? [];

        $this->response($data);
    }

    public function getPermissionList()
    {
        $service = new PermissionService();
        $result = $service->getUserPermissions();
        $this->response($result);
    }
}