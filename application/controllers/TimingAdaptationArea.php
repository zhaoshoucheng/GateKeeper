<?php
/***************************************************************
 * # 自适应
 * # user:ningxiangbing@didichuxing.com
 * # date:2018-09-10
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\TimingAdaptionAreaService;

/**
 * Class TimingAdaptationArea
 */
class TimingAdaptationArea extends MY_Controller
{
    protected $timingAdaptionAreaService;

    public function __construct()
    {
        parent::__construct();

        $this->timingAdaptionAreaService = new TimingAdaptionAreaService();
    }

    /**
     * 获取自适应区域列表
     *
     * @throws Exception
     */
    public function getAreaList()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);

        $data = $this->timingAdaptionAreaService->getAreaList($params);

        $this->response($data);
    }

    /**
     * 获取区域路口列表
     *
     * @return array
     * @throws Exception
     */
    public function getAreaJunctionList()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|is_natural_no_zero',
            'type' => 'in_list[-1,0,1,2,9]',
        ]);

        $params['type'] = $params['type'] ?? -1;

        $data = $this->timingAdaptionAreaService->getAreaJunctionList($params);

        $this->response($data);
    }

    /**
     * 获取区域实时报警信息
     * @param $params['city_id']     int 城市ID
     * @param $params['area_id']     int 区域ID
     * @param $params['alarm_type']  int 报警类型 0，全部; 1：过饱和；2：溢流；3：空放；4：轻度过饱和；。默认0
     * @param $params['ignore_type'] int 类型：0，全部，1，已忽略，2，未忽略。默认0
     * @throws Exception
     */
    public function realTimeAlarmList()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id'     => 'required|is_natural_no_zero',
            'area_id'     => 'required|is_natural_no_zero',
            'alarm_type'  => 'in_list[0,1,2,3,4]',
            'ignore_type' => 'in_list[0,1,2]',
        ]);

        $params['alarm_type']  = $params['alarm_type'] ?? 0;
        $params['ignore_type'] = $params['ignore_type'] ?? 0;

        $result = $this->timingAdaptionAreaService->realTimeAlarmList($params);

        $this->response($result);
    }

    /**
     * 人工标注报警信息
     *
     * @throws Exception
     */
    public function addAlarmRemark()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|trim|min_length[1]',
            'logic_flow_id' => 'required|trim|min_length[1]',
            'is_correct' => 'required|in_list[1,2]',
            'comment' => 'trim|min_length[1]',
        ]);

        $params['comment'] = $params['comment'] ?? '';


        $data = $this->timingAdaptionAreaService->addAlarmRemark($params);

        $this->response($data);
    }

    /**
     * 忽略报警
     *
     * @throws Exception
     */
    public function ignoreAlarm()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|is_natural_no_zero',
            'logic_flow_id' => 'required|trim|min_length[1]',
        ]);

        $result = $this->timingAdaptionAreaService->ignoreAlarm($params);

        $this->response($result);
    }

    /**
     * 更新自适应路口开关
     *
     * @throws Exception
     */
    public function junctionSwitch()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'is_upload' => 'required|in_list[0,1]',
            'area_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|trim|min_length[1]',
        ]);

        $result = $this->timingAdaptionAreaService->junctionSwitch($params);

        $this->response($result);
    }

    /**
     * 更新自适应区域开关
     *
     * @throws Exception
     */
    public function areaSwitch()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|is_natural_no_zero',
            'is_upload' => 'required|in_list[0,1]',
        ]);

        $result = $this->timingAdaptionAreaService->areaSwitch($params);

        $this->response($result);
    }

    /**
     * 获取区域指标折线图
     * @param $params['city_id']   int    城市ID
     * @param $params['area_id']   int    区域ID
     * @param $params['quota_key'] string 指标KEY
     * @throws Exception
     */
    public function getAreaQuotaInfo()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|is_natural_no_zero',
            'quota_key' => 'required|in_list[avgSpeed,stopDelay]',
        ]);

        $result = $this->timingAdaptionAreaService->getAreaQuotaInfo($params);

        $this->response($result);
    }

    /**
     * 获取时空图
     *
     * @throws Exception
     */
    public function getSpaceTimeMtraj()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|trim|min_length[1]',
            'logic_flow_id' => 'required|trim|min_length[1]',
        ]);

        $result = $this->timingAdaptionAreaService->getSpaceTimeMtraj($params);

        $this->response($result);
    }

    /**
     * 获取散点图
     *
     * @throws Exception
     */
    public function getScatterMtraj()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|trim|min_length[1]',
            'logic_flow_id' => 'required|trim|min_length[1]',
        ]);

        $result = $this->timingAdaptionAreaService->getScatterMtraj($params);

        $this->response($result);
    }

    /**
     * 获取排队长度图
     *
     * @throws Exception
     */
    public function getQueueLengthMtraj()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|trim|min_length[1]',
            'logic_flow_id' => 'required|trim|min_length[1]',
        ]);

        $result = $this->timingAdaptionAreaService->getQueueLengthMtraj($params);

        $this->response($result);
    }
}
