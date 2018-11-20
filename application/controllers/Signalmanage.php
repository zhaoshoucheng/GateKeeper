<?php
/***************************************************************
# 信控管理
# user:ningxiangbing@didichuxing.com
# date:2018-11-19
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\SignalmanageService;

class Signalmanage extends MY_Controller
{
    protected $signalmanageService;

    public function __construct()
    {
        parent::__construct();

        $this->signalmanageService = new signalmanageService();
    }
}