<?php

defined('BASEPATH') OR exit('No direct script access allowed');

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

}