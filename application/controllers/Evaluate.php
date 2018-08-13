<?php
/***************************************************************
# 评估类
# user:ningxiangbing@didichuxing.com
# date:2018-07-25
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Evaluate extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('evaluate_model');
        $this->load->model('redis_model');
        $this->load->config('realtime_conf');
    }

    /**
     * 获取全城路口列表
     * @param city_id    interger Y 城市ID
     * @return json
     */
    public function getCityJunctionList()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->evaluate_model->getCityJunctionList($data);

        $this->response($data);
    }

    /**
     * 获取指标列表
     * @param city_id    interger Y 城市ID
     * @return json
     */
    public function getQuotaList()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->evaluate_model->getQuotaList($data);

        $this->response($data);
    }

    /**
     * 获取相位（方向）列表
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @return json
     */
    public function getDirectionList()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        if(!isset($params['junction_id']) || empty(trim($params['junction_id']))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of junction_id is empty.';
            return;
        }

        $data['junction_id'] = $params['junction_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->evaluate_model->getDirectionList($data);

        $this->response($data);
    }

    /**
     * 获取路口指标排序列表
     * @param city_id     interger Y 城市ID
     * @param quota_key   string   Y 指标KEY
     * @param date        string   N 日期 格式：Y-m-d 默认当前日期
     * @param time_point  string   N 时间 格式：H:i:s 默认当前时间
     * @return json
     */
    public function getJunctionQuotaSortList()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'   => 'min:1',
                'quota_key' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (!array_key_exists($params['quota_key'], $this->config->item('real_time_quota'))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '指标 ' . html_escape($params['quota_key']) . ' 不存在！';
            return;
        }

        $data = [
            'city_id'    => intval($params['city_id']),
            'quota_key'  => strip_tags(trim($params['quota_key'])),
            'date'       => date('Y-m-d'),
            'time_point' => date('H:i:s'),
        ];

        if (!empty($params['date'])) {
            $data['date'] = date('Y-m-d', strtotime(strip_tags(trim($params['date']))));
        }

        if (!empty($params['time_point'])) {
            $data['time_point'] = date('H:i:s', strtotime(strip_tags(trim($params['time_point']))));
        }

        $result = $this->evaluate_model->getJunctionQuotaSortList($data);

        return $this->response($result);
    }

    /**
     * 获取指标趋势图
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @param quota_key   string   Y 指标KEY
     * @param flow_id     string   Y 相位ID
     * @param date        string   N 日期 格式：Y-m-d 默认当前日期
     * @param time_point  string   N 时间 格式：H:i:s 默认当前时间
     * @return json
     */
    public function getQuotaTrend()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'     => 'min:1',
                'junction_id' => 'nullunable',
                'quota_key'   => 'nullunable',
                'flow_id'     => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (!array_key_exists($params['quota_key'], $this->config->item('real_time_quota'))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '指标 ' . html_escape($params['quota_key']) . ' 不存在！';
            return;
        }

        $data = [
            'city_id'     => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'quota_key'   => strip_tags(trim($params['quota_key'])),
            'flow_id'     => strip_tags(trim($params['flow_id'])),
            'date'        => date('Y-m-d'),
            'time_point'  => date('H:i:s'),
        ];

        if (!empty($params['date'])) {
            $data['date'] = date('Y-m-d', strtotime(strip_tags(trim($params['date']))));
        }

        if (!empty($params['time_point'])) {
            $data['time_point'] = date('H:i:s', strtotime(strip_tags(trim($params['time_point']))));
        }

        $result = $this->evaluate_model->getQuotaTrend($data);

        return $this->response($result);
    }

    /**
     * 获取路口地图数据
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @return json
     */
    public function getJunctionMapData()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'     => 'min:1',
                'junction_id' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = [
            'city_id'     => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
        ];

        $result = $this->evaluate_model->getJunctionMapData($data);

        return $this->response($result);
    }

    /**
     * 指标评估对比
     * @param city_id         interger Y 城市ID
     * @param junction_id     string   Y 路口ID
     * @param quota_key       string   Y 指标KEY
     * @param flow_id         string   Y 相位ID
     * @param base_start_time string   N 基准开始时间 格式：yyyy-mm-dd hh:ii:ss
     * 例：2018-08-06 00:00:00 默认：上一周工作日开始时间（上周一 yyyy-mm-dd 00:00:00）
     * @param base_end_time   string   N 基准结束时间 格式：yyyy-mm-dd hh:ii:ss
     * 例：2018-08-07 23:59:59 默认：上一周工作日结束时间（上周五 yyyy-mm-dd 23:59:59）
     * @param evaluate_time   array    N 评估时间 有可能会有多个评估时间段，固使用json格式的字符串
     * evaluate_time 格式：
     * [
     *     [
     *         "start_time"=> "2018-08-01 00:00:00", // 开始时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-06 00:00:00
     *         "end_time"=> "2018-08-07 23:59:59"    // 结束时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-07 23:59:59
     *     ],
     *     ......
     * ]
     * @return json
     */
    public function quotaEvaluateCompare()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'     => 'min:1',
                'junction_id' => 'nullunable',
                'quota_key'   => 'nullunable',
                'flow_id'     => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (!array_key_exists($params['quota_key'], $this->config->item('real_time_quota'))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '指标 ' . html_escape($params['quota_key']) . ' 不存在！';
            return;
        }

        $data = [
            'city_id'     => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'quota_key'   => strip_tags(trim($params['quota_key'])),
            'flow_id'     => strip_tags(trim($params['flow_id'])),
        ];

        /**
         * 如果基准时间没有传，则默认：上周工作日
         * 如果评估时间没有传，则默认：本周工作日
         */
        if (empty($params['base_start_time'])) {
            // 上周一作为开始时间 Y-m-d H:i:s
            $baseStartTime = strtotime('monday last week');
        } else {
            $baseStartTime = strtotime($params['base_start_time']);
        }

        if (empty($params['base_end_time'])) {
            // 上周五作为结束时间 本周减去2天减1秒
            $baseEndTime = strtotime('monday this week') - 2 * 24 * 3600 - 1;
        } else {
            $baseEndTime = strtotime($params['base_end_time']);
        }

        // 用于返回
        $data['base_time_start_end'] = [
            'start' => date('Y-m-d H:i:s', $baseStartTime),
            'end'   => date('Y-m-d H:i:s', $baseEndTime),
        ];

        // 计算基准时间段具体每天日期
        for ($i = $baseStartTime; $i < $baseEndTime; $i += 24 * 3600) {
            $data['base_time'][] = $i;
        }

        if (empty($params['evaluate_time']) || !is_array($params['evaluate_time'])) {
            // 开始时间 本周一开始时间
            $startTime = strtotime('monday this week');

            // 当前星期几 如果星期一，结束时间要到当前时间 如果大于星期一，结束时间要前一天 如果是周日则向前推两天
            $week = date('w');
            if ($week == 0) { // 周日
                $endTime = strtotime(date('Y-m-d') . '-2 days') + 24 * 3600 - 1;
            } else if ($week == 1) { // 周一
                $endTime = time();
            } else {
                $endTime = strtotime(date('Y-m-d') . '-1 days') + 24 * 3600 - 1;
            }

            $params['evaluate_time'][] = [
                'start_time' => $startTime,
                'end_time'   => $endTime,
            ];
        } else {
            foreach ($params['evaluate_time'] as $k=>$v) {
                $params['evaluate_time'][$k] = [
                    'start_time' => isset($v['start_time']) ? strtotime($v['start_time']) : 0,
                    'end_time' => isset($v['end_time'])  ? strtotime($v['end_time']) : 0,
                ];
            }
        }

        // 用于返回
        $data['evaluate_time_start_end'] = [];

        // 处理评估时间，计算各评估时间具体日期
        foreach ($params['evaluate_time'] as $k=>$v) {
            for ($i = $v['start_time']; $i <= $v['end_time']; $i += 24 * 3600) {
                $data['evaluate_time'][$k][$i] = $i;
            }
            $data['evaluate_time_start_end'][$k] = [
                'start' => date('Y-m-d H:i:s', $v['start_time']),
                'end'   => date('Y-m-d H:i:s', $v['end_time']),
            ];
        }

        $result = $this->evaluate_model->quotaEvaluateCompare($data);

        return $this->response($result);
    }

    /**
     * 下载评估对比数据
     * @param
     * @return json
     */
    public function downloadEvaluateData()
    {
        $params = $this->input->post();

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
            'download_url' => '/api/evaluate/download?download_id='. $params['download_id']
        ];

        $this->response($data);
    }

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

        $fileName = "{$data['info']['junction_name']}_{$data['info']['quota_name']}_" . date('Ymd');

        $objPHPExcel = new PHPExcel();
        $objSheet = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('数据');

        $detailParams = [
            ['指标名', $data['info']['quota_name']],
            ['方向', $data['info']['direction']],
            ['基准时间', implode(' ~ ', $data['info']['base_time'])],
        ];
        foreach ($data['info']['evaluate_time'] as $key => $item) {
            $detailParams[] = ['评估时间'.$key, implode(' ~ ', $item)];
        }

        $detailParams[] = ['指标单位', $data['info']['quota_unit']];

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
            $cols_cnt = count($table[0]);
            $rows_index = $rows_cnt + $line - 1;
            $objSheet->getStyle("A{$line}:AW{$rows_index}")->applyFromArray($styles['content']);
            $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($styles['header']);
            $objSheet->getStyle("A{$line}:AW{$line}")->applyFromArray($styles['header']);

            $line += ($rows_cnt + 2);
        }

        if(!empty($data['evaluate'])) {

            foreach ($data['evaluate'] as $datum) {
                $table = $this->getExcelArray($datum);

                $objSheet->fromArray($table, NULL, 'A' . $line);

                $styles = $this->getExcelStyle();
                $rows_cnt = count($table);
                $cols_cnt = count($table[0]);
                $rows_index = $rows_cnt + $line - 1;
                $objSheet->getStyle("A{$line}:AW{$rows_index}")->applyFromArray($styles['content']);
                $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($styles['header']);
                $objSheet->getStyle("A{$line}:AW{$line}")->applyFromArray($styles['header']);

                $line += ($rows_cnt + 2);
            }
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
    }

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

    private function getExcelArray($data)
    {
        $table = [];

        $first = array_column(current($data), 1);
        array_unshift($first, "日期-时间");
        $table[] = $first;
        foreach ($data as $key => $value) {
            $column = array_column($value, 0);
            array_unshift($column, $key);
            $table[] = $column;
        }

        return $table;
    }
}
