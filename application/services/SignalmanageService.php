<?php
/**
 * 信控管理接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-19
 */

namespace Services;

class SignalmanageService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        // load model
        $this->load->model('signalmanage_model');
        $this->load->model('waymap_model');
        $this->load->model('common_model');

        // load config
        $this->load->config('signalmanage_conf');
    }

    /**
     * 路口管理-列表
     * @param $params['city_id']       int    Y 城市ID
     * @param $params['area_id']       int    N 区域ID
     * @param $params['junction_name'] string N 路口名称 模糊查询
     * @param $params['page']          int    Y 页码
     * @param $params['pagesize']      int    Y 每页数量
     * @param $params['orderby']       string N 排序 字段与规则用|隔开，多个排序用英文逗号隔开 例 city_id|desc,area_id|asc
     * @return array
     */
    public function junctionManageList($params)
    {
        if (empty($params)) {
            return [];
        }

        $whereData = [
            'city_id' => $params['city_id'],
        ];

        $areaWhere = 'city_id = ' . $params['city_id'];
        if (isset($params['area_id']) && intval($params['area_id']) >= 1) {
            $whereData['area_id'] = $params['area_id'];
            $areaWhere = ' and id = ' . $params['area_id'];
        }

        // where in
        $whereInData = [];

        // ['logic_junction_id'=>'name'] 用于匹配结果路口名称
        $junctionIdName = [];
        if (!empty($params['junction_name'])) {
            // 去路网获取相关路口信息
            $junctions = $this->waymap_model->getSuggestJunction($params['city_id'], $params['junction_name']);
            // 取出路口ID 组织为where in
            $whereInData = array_column($junctions, 'logic_junction_id');
            $junctionIdName = array_column($junctions, 'name', 'logic_junction_id');
        }

        $orderby = '';
        if (!empty($params['orderby'])) {
            $orderby = str_replace(['，', '|'], [',', ' '], $params['orderby']);
        }

        $result = $this->signalmanage_model->search($whereData, $whereInData, $orderby, ($params['page'] - 1), $params['pagesize']);
        if (empty($result) || !$result) {
            return [];
        }

        if (empty($junctionIdName)) {
            // 获取路口信息
            $junctionInfos = $this->waymap_model->getJunctionInfo(implode(',', array_column($result, 'junction_id')));
            $junctionIdName = array_column($junctionInfos, 'name', 'logic_junction_id');
        }

        // 通信方式
        $communicationMode = $this->config->item('communication_mode');

        // 获取城市名称
        $cityInfo = $this->common_model->search('open_city', 'city_name', 'city_id = ' . $params['city_id']);
        if (!empty($cityInfo)) {
            list($cityName) = array_column($cityInfo, 'city_name');
        } else {
            $cityName = '未知城市';
        }

        // 获取区域名称
        $areaIdName = [];
        $areaInfo = $this->common_model->search('area', 'id, area_name', $areaWhere);
        if (!empty($areaInfo)) {
            $areaIdName = array_column($areaInfo, 'area_name', 'id');
        }

        // 处理返回数据
        foreach ($result as $k=>$v) {
            $result[$k]['junction_name'] = $junctionIdName[$v['junction_id']] ?? '未知路口';
            $result[$k]['city_name'] = $cityName;
            $result[$k]['area_name'] = $areaIdName[$v['area_id']] ?? '未知区域';
            $result[$k]['communication_mode_name'] = $communicationMode[$v['communication_mode']]['name'];
        }

        // 获取总数
        $countInfo = $this->signalmanage_model->search($whereData, $whereInData, 'count(id) as total');

        return [
            'total'    => $countInfo['total'],
            'page'     => $params['page'],
            'pagesize' => $params['pagesize'],
            'dataList' => $result
        ];
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
     * @param $params['user']               string N 管理员
     * @return mixd
     */
    public function junctionManageEdit($params)
    {
        if (empty($params)) {
            throw new \Exception('表单不能为空！', ERR_DEFAULT);
        }

        $item = '新增';

        if (isset($params['id']) && intval($params['id']) >= 1) { // 编辑
            $id = $params['id'];
            unset($params['id']);
            $res = $this->signalmanage_model->edit($id, $params);
            $item = '编辑';
        } else { // 新增
            $res = $this->signalmanage_model->add($params);
        }

        if (!$res) {
            throw new \Exception($item . '失败！', ERR_DEFAULT);
        }

        return [];
    }
}
