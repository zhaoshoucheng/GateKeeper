<?php
/***************************************************************
# 参数管理
# user:ningxiangbing@didichuxing.com
# date:2018-11-19
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\ParametermanageService;
use Services\CommonService;

class Parametermanage extends MY_Controller
{
    protected $parametermanageService;
    protected $commonService;

    public function __construct()
    {
        parent::__construct();
        $this->commonService = new commonService();
        $this->parametermanageService = new parametermanageService();
    }

    /**
     * 获取参数列表
     * @param $params['city_id'] int    Y 城市ID
     * @param $params['area_id'] int    Y 区域ID
     * @param $params['is_default'] int    Y 1:默认, 0:非默认
     * @throws Exception
     */
    public function paramList()
    {
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $this->validate([
            'city_id'    => 'required|is_natural_no_zero',
            'area_id'    => 'required|integer',
            'is_default' => 'required|in_list[0,1]',
        ]);

        try {
            $data = $this->parametermanageService->paramList($params);
            $this->response($data);
        } catch (Exception $e) {
            $this->response('', 500, $e);
        }
    }

    /**
     * 获取优化参数配置阀值
     * @param $params['city_id'] int    Y 城市ID
     * @throws Exception
     */
    public function paramLimit()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'city_id'    => 'required|is_natural_no_zero',
        ]);

        $data = $this->parametermanageService->paramLimit($params);
        $this->response($data);
    }

    /**
     * 更新优化参数配置阀值
     *
     * @throws Exception
     */
    public function editParam()
    {
        $params = file_get_contents("php://input");
        $json = json_decode($params, true);
		$json = $this->security->xss_clean($json);

        try {
            list($upateResult,$paramLimitChanged,$offlineParamChanged,$rtParamChanged)=$this->parametermanageService->updateParam($json);

            //操作日志
            $areaList = $this->commonService->getAllCustomAreaByCityId($json["city_id"]);
            $areaMap = array_column($areaList,"areaName","areaId");
            $changeValues = [];
            if($paramLimitChanged){
                $changeValues[] = "路口延误阀值";
            }
            if($offlineParamChanged){
                $changeValues[] = "诊断评估指标阈值";
            }
            if($rtParamChanged){
                $changeValues[] = "实时概览指标阈值";
            }
            $areaName = $areaMap[$json["area_id"]] ?? "全城";
            $actionLog = sprintf("区域ID： %s，区域名称：%s，变更信息： %s",$json["area_id"],$areaName,implode(",",$changeValues));
            $this->insertLog("参数管理","编辑参数","编辑",$params,$actionLog);
            $this->response('');
        } catch (Exception $e) {
            $this->response('', 500, $e);
        }
    }

    public function realtimeAlarmParamList()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'city_id'    => 'required|is_natural_no_zero',
            'area_id'    => 'required|integer',
            'is_default' => 'required|in_list[0,1]',
        ]);

        try {
            $data = $this->parametermanageService->realtimeAlarmParamList($params);
            $this->response($data);
        } catch (Exception $e) {
            $this->response('', 500, $e);
        }
    }

    /**
     * 更新实时报警优化参数配置阀值
     *
     * @throws Exception
     */
    public function editRealtimeAlarmParam()
    {
        $params = file_get_contents("php://input");
        $json = json_decode($params, true);
        $json = $this->security->xss_clean($json);

        try {
            $this->parametermanageService->updateParam($json);
            $this->response('');
        } catch (Exception $e) {
            $this->response('', 500, $e);
        }
    }


}
