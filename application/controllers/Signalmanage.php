<?php
/***************************************************************
# 信控管理
# user:ningxiangbing@didichuxing.com
# date:2018-11-19
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\SignalmanageService;

class Signalmanage extends MY_Controller
{
    protected $signalmanageService;

    public function __construct()
    {
        parent::__construct();

        $this->load->config('signalmanage_conf');

        $this->signalmanageService = new signalmanageService();
    }

    /**
     * 路口管理-列表
     * @param $params['city_id']       int    Y 城市ID
     * @param $params['area_id']       int    N 区域ID
     * @param $params['junction_name'] string N 路口名称 模糊查询
     * @param $params['page']          int    Y 页码
     * @param $params['pagesize']      int    Y 每页数量
     * @param $params['orderby']       string N 排序 字段与规则用|隔开，多个排序用英文逗号隔开 例 city_id|desc,area_id|asc
     * @return json
     */
    public function junctionManageList()
    {
        $params = $this->input->post(null, true);

        // 校验参数
        $this->validate([
            'junction_id' => 'trim|min_length[4]',
            'city_id'     => 'required|is_natural_no_zero',
            'area_id'     => 'is_natural_no_zero',
            'page'        => 'required|is_natural',
            'pagesize'    => 'required|is_natural_no_zero',
            'orderby'     => 'min_length[2]',
        ]);

        $result = $this->signalmanageService->junctionManageList($params);

        $this->response($result);
    }

    /**
     * 路口管理-编辑
     * @param $params['id']                 int    N 唯一ID 大于0时：修改
     * @param $params['junction_id']        string Y 路口ID
     * @param $params['city_id']            int    Y 城市ID
     * @param $params['area_id']            int    Y 区域ID
     * @param $params['manufacturer']       string Y 厂商名称
     * @param $params['son_junction_id']    string N 子路口ID
     * @param $params['junction_type']      string N 路口类型
     * @param $params['mfg_junction_id']    strign N 厂商路口ID
     * @param $params['communication_mode'] int    N 通信方式 0：网口-RJ45 1：串口-RS232 2：无线-3G 3：无线-4G 4：无线-5G
     * @param $params['semaphore_addr']     string N 信号机地址
     * @param $params['IPv4']               string N IPv4地址
     * @param $params['IPv6']               string N IPv6地址
     * @param $params['junction_comment']   string N 路口备注
     * @return json
     */
    public function junctionManageEdit()
    {
        $params = $this->input->post(null, true);

        // 校验参数
        $this->validate([
            'id'                 => 'is_natural_no_zero',
            'junction_id'        => 'required|trim|min_length[4]',
            'city_id'            => 'required|is_natural_no_zero',
            'area_id'            => 'required|is_natural_no_zero',
            'manufacturer'       => 'required|trim|is_natural',
            'son_junction_id'    => 'min_length[4]',
            'junction_type'      => 'min_length[2]',
            'mfg_junction_id'    => 'min_length[4]',
            'communication_mode' => 'in_list(' . implode(',', array_cloumn($this->config->item('communication_mode'), 'id')) . ')',
            'semaphore_addr'     => 'min_length[2]',
            'IPv4'               => 'min_length[2]',
            'IPv6'               => 'min_length[2]',
            'junction_comment'   => 'min_length[1]',
        ]);

        // 当前用户
        $params['user'] = $this->username;

        $result = $this->signalmanageService->junctionManageEdit($params);

        $this->response($result);
    }

    /**
     * 路口管理-删除
     */
    public function junctionManageDel()
    {
        $params = $this->input->post(null, true);
    }
}