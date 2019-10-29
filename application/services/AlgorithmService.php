<?php

namespace Services;

/**
 * Class AdaptionLogService
 * @package Services
 * @property \Adapt_model $adapt_model
 * @property \Realtime_model $realtime_model
 */
class AlgorithmService extends BaseService
{
    protected $helperService;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('redis_model');
        $this->load->model('algorithm_model');
    }
    
    public function getAllSelector($params)
    {
        $result = $this->algorithm_model->getAllSelector();
        if (!$result) {
            throw new \Exception('获取算法数据失败', ERR_DATABASE);
        }
        return $result;
    }
}