<?php
/***************************************************************
# 参数管理
# user:ningxiangbing@didichuxing.com
# date:2018-11-19
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\ParametermangeService;

class Parametermange extends MY_Controller
{
    protected $parametermangeService;

    public function __construct()
    {
        parent::__construct();

        $this->parametermangeService = new parametermangeService();
    }
}