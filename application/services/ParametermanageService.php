<?php
/**
 * 信控管理接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-19
 */

namespace Services;

class ParametermangeService extends BaseService
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
        $ret = [];
        for ($i = 1; $i < 5; $i++) {
            $temp = [];
            for ($j = 0; $j < 24; $j++) {
                array_push(
                    $temp,
                    $this->parametermanage_model->getParameterByArea(
                        $cityId,
                        $areaId,
                        $isDefault,
                        $i,
                        $j,
                    ),
                );
            }
            $ret[$i] = $temp;
        }
        return ['status'=>$ret];
    }

    /**
     * 更新参数优化配置
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function updateParamList($param)
    {
    }
}
