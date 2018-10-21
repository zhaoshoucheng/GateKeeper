<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午6:58
 */

namespace Services;

/**
 * Class HelperService
 * @package Services
 */
class HelperService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('redis_model');
        $this->load->model('realtime_model');
    }

    /**
     * 获取最新的hour
     *
     * @param $cityId
     *
     * @return array|bool|string
     * @throws \Exception
     */
    public function getLastestHour($cityId)
    {
        $hour = $this->redis_model->getData('its_realtime_lasthour_' . $cityId);

        if($hour) {
            return $hour;
        }

        $res = $this->realtime_model->getLastestHour($cityId);

        if(!$res) {
            throw new \Exception('获取 hour 失败', ERR_DATABASE);
        }

        return $res['hour'];
    }
}