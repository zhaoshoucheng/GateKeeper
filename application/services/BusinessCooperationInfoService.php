<?php

namespace Services;

/**
 * Class AdaptionLogService
 * @package Services
 * @property \Businesscooperationinfo_model $Businesscooperationinfo_model
 * @property \Redis_model $redis_model
 */
class BusinessCooperationInfoService extends BaseService
{
    protected $helperService;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('redis_model');
        $this->load->model('Businesscooperationinfo_model');
    }

    public function insert($params)
    {
        $redisKey = sprintf("bci_%s",$_SERVER["REMOTE_ADDR"]);
        $value=$this->redis_model->getData($redisKey);
        if($value!=""){
            throw new \Exception('请求频率过快', ERR_OPERATION_LIMIT);
        }
        $result = $this->Businesscooperationinfo_model->insert($params);
        if (!$result) {
            throw new \Exception('写入商务合作信息失败', ERR_DATABASE);
        }
        $this->redis_model->setEx($redisKey,1,10);
        return true;
    }
}