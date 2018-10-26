<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/26
 * Time: 下午1:10
 */

namespace Services;


class JunctionService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('waymap_model');
        $this->load->config('report_conf');
    }

    public function queryQuotaInfo($params)
    {

    }
}