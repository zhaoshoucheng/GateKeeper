<?php
/***************************************************************
# 干线类
# user:ningxiangbing@didichuxing.com
# date:2018-08-21
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Road extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('road_model');
        $this->load->config('junctioncomparison_conf');
        $this->load->config('evaluate_conf');
    }

    /**
     * 查询干线列表
     * @param city_id interger Y 城市ID
     * @return json
     */
    public function queryRoadList()
    {
        $params = $this->input->post();

        // 校验参数
        $validate = Validate::make($params, [
                'city_id'   => 'min:1',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $cityId = intval($params['city_id']);

        $result = $this->road_model->queryRoadList($cityId);

        return $this->response($result);
    }

    /**
     * 新增干线
     * @param city_id        interger Y 城市ID
     * @param road_name      string   Y 干线名称
     * @param junction_ids   array    Y 干线路口ID
     * @param road_direction interger Y 干线方向 1：东西 2：南北
     * @return json
     */
    public function addRoad()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $validate = Validate::make($params, [
                'city_id'        => 'min:1',
                'road_name'      => 'nullunable',
                'road_direction' => 'min:1',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (empty($params['junction_ids']) || !is_array($params['junction_ids'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数 junction_ids 须为数组且不能为空！';
            return;
        }

        if (!isset($params['junction_ids']) || count($params['junction_ids']) < 4) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '请至少选择4个路口做为干线！';
            return;
        }

        $roadDirectionConf = $this->config->item('road_direction');
        if (!array_key_exists(intval($params['road_direction']), $roadDirectionConf)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '请选择正确的干线方向！';
            return;
        }

        $data = [
            'city_id'        => intval($params['city_id']),
            'road_name'      => strip_tags(trim($params['road_name'])),
            'junction_ids'   => $params['junction_ids'],
            'road_direction' => intval($params['road_direction']),
        ];

        $result = $this->road_model->addRoad($data);
        if ($result['errno'] != 0) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $result['errmsg'];
            return;
        }

        $this->errmsg = 'success.';
        return;
    }

    /**
     * 编辑干线
     * @param city_id        interger Y 城市ID
     * @param road_id        string   Y 干线ID
     * @param road_name      string   Y 干线名称
     * @param junction_ids   array    Y 干线路口ID
     * @param road_direction interger Y 干线方向 1：东西 2：南北
     * @return json
     */
    public function editRoad()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $validate = Validate::make($params, [
                'city_id'        => 'min:1',
                'road_name'      => 'nullunable',
                'road_id'        => 'nullunable',
                'road_direction' => 'min:1',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (empty($params['junction_ids']) || !is_array($params['junction_ids'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数 junction_ids 须为数组且不能为空！';
            return;
        }

        if (count($params['junction_ids']) < 4) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '请至少选择4个路口做为干线！';
            return;
        }

        $roadDirectionConf = $this->config->item('road_direction');
        if (!array_key_exists(intval($params['road_direction']), $roadDirectionConf)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '请选择正确的干线方向！';
            return;
        }

        $data = [
            'city_id'        => intval($params['city_id']),
            'road_id'        => strip_tags(trim($params['road_id'])),
            'road_name'      => strip_tags(trim($params['road_name'])),
            'junction_ids'   => $params['junction_ids'],
            'road_direction' => intval($params['road_direction']),
        ];

        $result = $this->road_model->editRoad($data);

        if ($result['errno'] != 0) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $result['errmsg'];
            return;
        }

        $this->errmsg = 'success.';
        return;
    }

    /**
     * 删除干线
     * @param city_id interger Y 城市ID
     * @param road_id string   Y 干线ID
     * @return json
     */
    public function delete()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $validate = Validate::make($params, [
                'city_id' => 'min:1',
                'road_id' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = [
            'city_id' => intval($params['city_id']),
            'road_id' => strip_tags(trim($params['road_id'])),
        ];

        $result = $this->road_model->delete($data);
        if ($result['errno'] != 0) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $result['errmsg'];
            return;
        }

        $this->errmsg = 'success.';
        return;
    }

    /**
     * 查询干线详情
     * @param city_id interger Y 城市ID
     * @param road_id string   Y 干线ID
     * @return json
     */
    public function getRoadDetail()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $validate = Validate::make($params, [
                'city_id' => 'min:1',
                'road_id' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = [
            'city_id' => intval($params['city_id']),
            'road_id' => strip_tags(trim($params['road_id'])),
        ];

        $result = $this->road_model->getRoadDetail($data);

        return $this->response($result);
    }

    /**
     * 获取全部的干线信息
     */
    public function getAllRoadDetail()
    {
        $params = $this->input->post(NULL, TRUE);

        $validator = Validator::make($params, [
            'city_id' => 'required',
        ]);

        if($validator->fail()) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validator->firstError();
            return;
        }

        // 异常处理
        try {
            $data = $this->road_model->getAllRoadDetail($params);
            return $this->response($data);
        } catch (Exception $e) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $e->getMessage();
            return;
        }
    }

    /**
     * 获取干线评估指标
     */
    public function getQuotas()
    {
        $this->response($this->config->item('road'));
    }

    /**
     * 干线评估
     */
    public function comparison()
    {
        $params = $this->input->post();

        // 数据校验
        $validator = Validator::make($params, [
            'city_id' => 'required;numeric',
            'road_id' => 'required',
            'quota_key' => 'required',
            'direction' => 'required;in:1,2',
            'base_start_date' =>'required;date:Y-m-d',
            'base_end_date' =>'required;date:Y-m-d',
            'evaluate_start_date' =>'required;date:Y-m-d',
            'evaluate_end_date' =>'required;date:Y-m-d',
        ]);

        if($validator->fail()) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validator->firstError();
            return;
        }

        // 异常处理
        try {
            $data = $this->road_model->comparison($params);
            return $this->response($data);
        } catch (Exception $e) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $e->getMessage();
            return;
        }
    }

    /**
     * 获取数据下载链接
     */
    public function downloadEvaluateData()
    {
        $params = $this->input->post(NULL, TRUE);

        if(!isset($params['download_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = "download_id 的值不能为空";
            return;
        }

        $key = $this->config->item('quota_evaluate_key_prefix') . $params['download_id'];

        if(!$this->redis_model->getData($key)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = "请先评估再下载";
            return;
        }

        $data = [
            'download_url' => '/api/road/download?download_id='. $params['download_id']
        ];

        $this->response($data);
    }

    /**
     * Excel 文件下载
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function download()
    {

        $params = $this->input->get();

        if(!isset($params['download_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = "download_id 的值不能为空";
            return;
        }

        $key = $this->config->item('quota_evaluate_key_prefix') . $params['download_id'];

        if(!($data = $this->redis_model->getData($key))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = "请先评估再下载";
            return;
        }

        $data = json_decode($data, true);

        $fileName = "{$data['info']['road_name']}_" . date('Ymd');

        $objPHPExcel = new PHPExcel();
        $objSheet = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('数据');

        $detailParams = [
            ['指标名', $data['info']['quota_name']],
            ['方向', $data['info']['direction'] ?? ''],
            ['基准时间', implode(' ~ ', $data['info']['base_time'])],
            ['评估时间', implode(' ~ ', $data['info']['evaluate_time'])],
            ['指标单位', $data['info']['quota_unit']],
        ];

        $objSheet->mergeCells('A1:F1');
        $objSheet->setCellValue('A1', $fileName);
        $objSheet->fromArray($detailParams, NULL, 'A4');

        $styles = $this->getExcelStyle();
        $objSheet->getStyle('A1')->applyFromArray($styles['title']);
        $rows_idx = count($detailParams) + 3;
        $objSheet->getStyle("A4:A{$rows_idx}")->getFont()->setSize(12)->setBold(true);

        $line = 6 + count($detailParams);

        if(!empty($data['base'])) {

            $table = $this->getExcelArray($data['base']);

            $objSheet->fromArray($table, NULL, 'A' . $line);

            $styles = $this->getExcelStyle();
            $rows_cnt = count($table);
            $cols_cnt = count($table[0]) - 1;
            $rows_index = $rows_cnt + $line - 1;

            $objSheet->getStyle("A{$line}:".$this->intToChr($cols_cnt) . $rows_index)->applyFromArray($styles['content']);
            $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($styles['header']);
            $objSheet->getStyle("A{$line}:".$this->intToChr($cols_cnt) . $line)->applyFromArray($styles['header']);

            $line += ($rows_cnt + 2);
        }

        if(!empty($data['evaluate'])) {

            $table = $this->getExcelArray($data['evaluate']);

            $objSheet->fromArray($table, NULL, 'A' . $line);

            $styles = $this->getExcelStyle();
            $rows_cnt = count($table);
            $cols_cnt = count($table[0]) - 1;
            $rows_index = $rows_cnt + $line - 1;
            $objSheet->getStyle("A{$line}:".$this->intToChr($cols_cnt) . $rows_index)->applyFromArray($styles['content']);
            $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($styles['header']);
            $objSheet->getStyle("A{$line}:".$this->intToChr($cols_cnt) . $line)->applyFromArray($styles['header']);

            $line += ($rows_cnt + 2);
        }

        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

        header('Content-Type: application/x-xls;');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$fileName . '.xls');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: 0'); // Date in the past
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        ob_end_clean();
        $objWriter->save('php://output');
        exit();
    }

    /**
     * 获取 Excel 单元格格式
     * @return array
     */
    private function getExcelStyle() {
        $title_style = array(
            'font' => array(
                'bold' => true,
                'size '=> 16,
                'color'=>array(
                    'argb' => '00000000',
                ),
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array(
                    'argb' => '00FFFF00',
                ),
            ),
        );

        $headers_style = array(
            'font' => array(
                'bold' => true,
                'size '=> 12,
                'color'=>array(
                    'argb' => '00000000',
                ),
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array(
                    'argb' => '00DCDCDC',
                ),
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );

        $content_style = array(
            'borders' => array (
                'allborders' => array (
                    'style' => PHPExcel_Style_Border::BORDER_THIN,  //设置border样式
                    //'style' => PHPExcel_Style_Border::BORDER_THICK, //另一种样式
                    'color' => array ('argb' => '00000000'),     //设置border颜色
                ),
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );

        return array(
            'title'  => $title_style,
            'header' => $headers_style,
            'content'=> $content_style,
        );

    }

    /**
     * 获取 Excel 单元格填充元素
     *
     * @param $data
     * @return array
     */
    private function getExcelArray($data)
    {
        $timeArray = ["00:00", "00:30","01:00","01:30", "02:00", "02:30", "03:00", "03:30",
            "04:00", "04:30", "05:00", "05:30", "06:00", "06:30", "07:00", "07:30",
            "08:00", "08:30", "09:00", "09:30", "10:00", "10:30", "11:00", "11:30",
            "12:00", "12:30", "13:00", "13:30", "14:00", "14:30", "15:00", "15:30",
            "16:00", "16:30", "17:00", "17:30", "18:00", "18:30", "19:00", "19:30",
            "20:00", "20:30", "21:00", "21:30", "22:00", "22:30", "23:00", "23:30"];

        $table = [];

        $table[] = $timeArray;
        array_unshift($table[0], "日期-时间");

        $data = array_map(function ($value) {
            return array_column($value, 1, 0);
        }, $data);

        foreach ($data as $key => $value) {
            $column = [];
            $column[] = $key;
            foreach ($timeArray as $item) {
                $column[] = $value[$item] ?? '-';
            }
            $table[] = $column;
        }
        //echo json_encode($table);die();
        return $table;
    }

    /**
     * int 转 char (Excel 中)
     *
     * @param $index
     * @param int $start
     * @return string
     */
    private function intToChr($index, $start = 65) {
        $str = '';
        if (floor($index / 26) > 0) {
            $str .= $this->intToChr(floor($index / 26) - 1);
        }
        return $str . chr($index % 26 + $start);
    }
}
