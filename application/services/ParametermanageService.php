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
     * 获取参数优化配置阀值
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function paramLimit($params)
    {
        $cityId = $params['city_id'];
        $isDefault = $params['is_default'];
        $res = $this->parametermanage_model->getParameterLimit($cityId, $isDefault);
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
    public function updateParam($param)
    {
        if (isset($param['param_limits']) {
            if (!$this->parametermanage_model->updateParameterLimit($param['param_limits'])) {
                return false;
            }
        }
        if (isset($param['params'])) {
            $data = $param['params'];
            foreach ($data as $d) {
                if (!$this->parametermanage_model->updateParameter($d)) {
                    return false;
                }
            }
        }
        return true;
    }
}
