<?php
/**
 * 区域分析报告模块业务逻辑
 */

namespace Services;

class AreaReportService extends BaseService{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('report_conf');

    }
}