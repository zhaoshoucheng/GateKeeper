<?php
/***************************************************************
# 公共方法类
# 1、获取路口所属行政区域及交叉节点信息
# user:ningxiangbing@didichuxing.com
# date:2018-08-23
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\InsidecommonService;

class Insidecommon extends CI_Controller
{
    protected $insidecommonService;

    public function __construct()
    {
        parent::__construct();

        $this->insidecommonService = new insidecommonService();
    }

    /**
     * 区域数据接口
     * 权限sso用，获取开城城市列表、行政区域、自定义区域、自定义干线、所有路口
     * @param $params['cityId']   long N 城市ID 默认传递
     * @param $params['areaId']   long N 城市ID areaType非零情况下必填，取自开城城市列表返回接口中的areaId
     * @param $params['areaType']  int  Y 0：开城城市列表，1：行政区域 ，2：自定义区域，3：干线，4：路口
     * @return json
     */
    public function areaData()
    {
        $params = $this->input->post(null, true);

        // 校验参数
        $this->validate([
            'areaType' => 'required|is_natural',
        ]);

        if (in_array($params['areaType'], [1, 2, 3, 4])) {
            if (intval($params['areaId']) < 1) {
                throw new \Exception('areaId不能为空！', ERR_PARAMETERS);
            }
        }

        switch ($params['areaType']) {
            case 1:
                // 根据城市ID获取所有行政区域
                $result = $this->insidecommonService->getAllAdminAreaByCityId($params['areaId']);
                break;

            case 2:
                // 根据城市ID获取所有自定义区域
                $result = $this->insidecommonService->getAllCustomAreaByCityId($params['areaId']);
                break;

            case 3:
                // 根据城市ID获取所有自定义干线
                $result = $this->insidecommonService->getAllCustomRoadByCityId($params['areaId']);
                break;

            case 4:
                // 根据城市ID获取所有路口
                $result = $this->insidecommonService->getAllJunctionByCityId($params['cityId'], $params['areaId']);
                break;
            default:
                // 获取开城城市列表
                $result = $this->insidecommonService->getOpenCityList();
                break;
        }

        $this->response($result);
    }
}
