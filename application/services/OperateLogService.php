<?php

namespace Services;

/**
 * Class OperateLogService
 * @package Services
 * @property \OperateLog_model $operateLog_model
 */
class OperateLogService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
//        $this->load->model('operateLog_model');
    }

    public function pageList($params)
    {
//        $result = $this->operateLog_model->pageList($params);
//        if (!$result) {
//            throw new \Exception('获取日志数据失败', ERR_DATABASE);
//        }
//        return $result;
    }

    public function insertLog($params)
    {
//        $result = $this->operateLog_model->insertLog($params);
//        if (!$result) {
//            throw new \Exception('插入日志数据失败', ERR_DATABASE);
//        }
//        return $result;
    }
}