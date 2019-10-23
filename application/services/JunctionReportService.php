<?php
/**
 * 路口分析报告模块业务逻辑
 */

namespace Services;

class JunctionReportService extends BaseService{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('report_conf');

    }
}