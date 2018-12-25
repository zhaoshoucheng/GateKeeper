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
            $areaWhere .= ' and id = ' . $params['area_id'];
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
        $countInfo = $this->signalmanage_model->count($whereData, $whereInData, 'count(id) as total');

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

    /**
     * 路口管理-删除
     * @param $id int Y 唯一ID
     * @return mixed
     */
    public function junctionManageDel($id)
    {
        $res = $this->signalmanage_model->del($id);
        if (!$res) {
            throw new \Exception("删除失败！", ERR_DEFAULT);
        }

        return [];
    }

    /**
     * 导出下载
     * @param $params['city_id']       int    Y 城市ID
     * @param $params['area_id']       int    N 区域ID
     * @param $params['junction_name'] string N 路口名称 模糊查询
     * @return mixd
     */
    public function download($params)
    {
        if (empty($params)) {
            throw new \Exception("请选择城市！", ERR_PARAMETERS);
        }

        $whereData = [
            'city_id' => $params['city_id'],
        ];

        $areaWhere = 'city_id = ' . $params['city_id'];
        if (isset($params['area_id']) && intval($params['area_id']) >= 1) {
            $whereData['area_id'] = $params['area_id'];
            $areaWhere .= ' and id = ' . $params['area_id'];
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

        $result = $this->signalmanage_model->search($whereData, $whereInData);
        if (empty($result) || !$result) {
            throw new \Exception("此查询条件下没有数据！", ERR_DEFAULT);
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
            $result[$k]['area_name'] = $areaIdName[$v['area_id']] ?? '未知区域';
            $result[$k]['communication_mode_name'] = $communicationMode[$v['communication_mode']]['name'];
        }

        $objPHPExcel = new \PHPExcel();
        $objSheet    = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('数据');

        // 横向单元格标识
        $cellName = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK',
            'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV',
            'AW', 'AX', 'AY', 'AZ'
        ];

        // 设置需要的title
        $cellTitle = [
            [
                'name' => '路口名称',
                'key'  => 'junction_name',
            ],
            [
                'name' => '区域',
                'key'  => 'area_name',
            ],
            [
                'name' => '厂商名称',
                'key'  => 'manufacturer',
            ],
            [
                'name' => '逻辑路口ID',
                'key'  => 'junction_id',
            ],
            [
                'name' => '子路口ID',
                'key'  => 'son_junction_id',
            ],
            [
                'name' => '路口类型',
                'key'  => 'junction_type',
            ],
            [
                'name' => '厂商路口ID',
                'key'  => 'mfg_junction_id',
            ],
            [
                'name' => '通信方式',
                'key'  => 'communication_mode_name',
            ],
            [
                'name' => '信号机地址',
                'key'  => 'semaphore_addr',
            ],
            [
                'name' => 'IPv4地址',
                'key'  => 'IPv4',
            ],
            [
                'name' => 'IPv6地址',
                'key'  => 'IPv6',
            ],
            [
                'name' => '路口备注',
                'key'  => 'junction_comment',
            ],
        ];

        // 行标初始位置
        $rowIdx = 1;
        // 设置表格title
        $cellCount = count($cellTitle);
        for ($i = 0; $i < $cellCount; $i ++) {
            $objSheet->setCellValue($cellName[$i] . $rowIdx, $cellTitle[$i]['name']);
        }

        $rowIdx++;
        foreach ($result as $k=>$v) {
            foreach ($cellTitle as $kk=>$vv) {
                $objSheet->setCellValue($cellName[$kk] . $rowIdx, $v[$vv['key']]);
            }
            $rowIdx++;
        }

        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        header('Content-Type: application/x-xls;');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $fileName . '.xls');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: 0'); // Date in the past
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        ob_end_clean();
        $objWriter->save('php://output');
        exit();
    }
}
