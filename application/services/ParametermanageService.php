<?php
/**
 * 信控管理接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-19
 */

namespace Services;

class ParametermanageService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->helperService = new HelperService();
        $this->load->model('parametermanage_model');
    }

    /**
     * 获取参数优化配置
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function paramList($params)
    {
        $cityId = $params['city_id'];
        $areaId = $params['area_id'];
        $isDefault = $params['is_default'];
        $res = $this->parametermanage_model->getParameterByArea($cityId, $areaId, $isDefault);
        return $res;
    }

    /**
     * 更新参数优化配置
     *
     * @param $params
     *
     * @return bool
     * @throws \Exception
     */
    public function updateParamList($param)
    {
        $res = $this->parametermanage_model->updateParameter($param);
        return $res;
    }
}
