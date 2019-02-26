<?php

namespace Services;

/**
 * Class AdaptionLogService
 * @package Services
 * @property \Adapt_model $adapt_model
 * @property \Realtime_model $realtime_model
 */
class AdaptionLogService extends BaseService
{
    protected $helperService;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('redis_model');
        $this->load->model('adapt_model');
    }

    public function insert($params)
    {
        $result = $this->adapt_model->insertAdaptLog($params);
        if (!$result) {
            throw new \Exception('写入调度日志失败', ERR_DATABASE);
        }
        return true;
    }

    public function pageList($params)
    {
        $result = $this->adapt_model->pageList($params);
        if (!$result) {
            throw new \Exception('获取调度数据失败', ERR_DATABASE);
        }
        return $result;
    }
}