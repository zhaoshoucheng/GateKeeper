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

    }

    /**
     * 指标评估对比
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @param quota_key   string   Y 指标KEY
     * @param flow_id     string   Y 相位ID
     * @param base_start_time string N 基准开始时间 格式：yyyy-mm-dd hh:ii:ss
     *                                 例：2018-08-06 00:00:00 默认：当前日期前一天的前6天开始时间
     * @param base_end_time   string N 基准结束时间 格式：yyyy-mm-dd hh:ii:ss
     *                                 例：2018-08-06 00:00:00 默认：当前日期前一天结束时间
     * @param evaluate_time   string N 评估时间 有可能会有多个评估时间段，固使用json格式的字符串
     * evaluate_time 格式：
     *     [
     *         {
     *             "start_time": "2018-08-01", // 开始时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-06 00:00:00
     *             "end_time": "2018-08-03"  // 结束时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-06 00:00:00
     *         },
     *         ......
     *     ]
     * @return json
     */
    public function quotaEvaluateCompare()
    {

    }

    /**
     * 下载评估对比数据
     * @param
     * @return json
     */
    public function downloadEvaluateData()
    {
        $params = $this->input->get();

        $downloadId = $params['download_id'];
    }

    public function createEvaluateData()
    {
        $fileName = 'CityName_DataTypeName_ObjectName_QuotaName_Date.xls';

        $objPHPExcel = new PHPExcel();
        $objSheet = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('数据');

        $detailParams = [
            ['指标名', '延误时间'],
            ['方向', '所有方向'],
            ['基准时间', '无'],
            ['评估时间', '2018/07/31 ~ 2018/08/06'],
            ['指标单位', '秒']
        ];

        $data = '日期-时间,00:00,00:30,01:00,01:30,02:00,02:30,03:00,03:30,04:00,04:30,05:00,05:30,06:00,06:30,07:00,07:30,08:00,08:30,09:00,09:30,10:00,10:30,11:00,11:30,12:00,12:30,13:00,13:30,14:00,14:30,15:00,15:30,16:00,16:30,17:00,17:30,18:00,18:30,19:00,19:30,20:00,20:30,21:00,21:30,22:00,22:30,23:00,23:30;2018-07-31,3.349557356,18.42246639,19.13720078,17.96736487,13.65025043,60.91609086,12.52149391,3.407569885,17.35880025,10.7147135,18.92882919,22.16095198,21.02782021,21.00626911,69.04743811,44.05175975,41.73895326,69.75081582,82.52639768,71.21324094,85.59893219,67.7713236,89.01594172,100.5507902,106.1185187,82.12918967,96.08954016,51.04746644,102.7442062,44.73388104,55.15384418,32.79435006,33.77756372,43.68288193,60.5056112,59.01960889,30.21897348,45.80438179,64.23501538,51.19863332,126.632183,101.1835215,68.4469948,45.93828286,40.0465117,25.33040331,20.65572593,21.56549188;2018-08-01,6.391821659,7.348034846,18.15960952,19.28989775,30.8013497,5.138392262,0,39.46421262,22.82251848,16.62957982,16.54503201,18.26267859,14.41575272,25.07652148,28.64933301,30.46313296,28.77706179,50.54206826,67.8510718,57.71756149,81.65598899,79.09774687,105.2635089,102.24419,73.5317413,104.3684524,119.8420319,118.9820775,138.655383,67.81215986,112.9645476,151.5669837,143.6863881,80.48803969,113.8260974,91.37475576,105.1994259,94.25983467,130.6627478,143.715536,120.6342257,97.94208144,102.813971,34.0277145,32.48359088,24.42489423,12.42185124,15.54046777;2018-08-02,5.577595827,19.74942062,11.14287967,7.673844761,7.613438328,10.52387039,-,25.09803385,16.55607399,26.95180257,20.15049103,16.055005,3.584329213,17.62268293,47.09699053,36.09634439,48.67549868,42.43235402,79.21000296,65.83001109,90.25205199,69.53213199,62.63909178,69.03716325,72.37833622,146.1809143,95.83046892,99.5107024,79.83302795,47.96203057,117.4683015,103.2829578,61.09636619,97.06325908,108.5150336,45.36438301,65.84427156,88.10494201,123.7444024,107.3620611,131.0789842,101.3548717,84.55898542,48.54999435,45.5178666,23.86051129,18.48152454,30.1537679;2018-08-03,4.038736731,23.76043192,22.37141974,18.78091027,18.05914205,22.8570292,12.17528816,11.3409996,28.60450375,24.76443481,26.60415346,24.96228055,3.359496463,9.484265143,29.66495504,28.06301383,18.28126136,34.22816029,75.57369977,75.70989988,75.01606154,66.60530946,102.0761009,114.6474094,50.58501263,58.0499762,60.60582369,28.33902675,32.47332629,29.76009487,107.1191686,126.8533594,84.8332164,67.75795479,66.88651298,94.19848887,76.59097159,80.93577003,134.5490393,147.2217222,158.3793782,90.76863133,103.7324068,65.65766135,79.62901808,28.76364303,21.72695698,30.00978339;2018-08-04,13.83075744,19.76514879,0.888944621,14.17256885,22.85333631,18.33332417,13.80117692,7.699238334,15.04850324,28.52699552,15.88906683,16.89387879,14.3074001,12.06959804,21.51834833,14.27009288,20.76931414,46.30292566,59.20982842,74.06612944,61.68432319,81.23964675,83.52882241,122.7294415,114.6858081,71.74922671,127.4388161,118.249023,143.1626053,148.4109035,120.5961223,95.14217443,156.611086,234.1272764,166.8569561,151.0349885,118.4648779,127.4074828,172.4242848,160.646325,208.5202584,165.0588516,113.774616,61.80726859,107.5864216,32.91102584,26.61148544,3.587728066;2018-08-05,15.78321119,15.27709087,21.87027561,8.636734163,21.77604826,4.917974854,37.43366969,25.17797256,39.78455935,28.46207495,24.62792015,11.39141262,16.91587522,24.98360256,27.82119757,18.31865474,18.85133199,49.06167198,39.83380486,65.91708276,65.24921597,66.35088496,83.1820496,97.33105274,104.5657688,177.7687973,141.7455888,83.74128719,131.8737911,127.5507792,136.2576533,120.0655156,119.0620493,116.1547016,121.5576991,132.4693585,128.6866542,134.1877883,114.7841929,130.4877846,156.7251787,84.71670324,73.99685533,59.4186626,31.87274977,23.48204843,17.59058676,9.768305426;2018-08-06,23.67400234,11.73544025,26.84563309,11.62993526,16.52755588,21.01316746,27.42561066,22.68220011,23.7038697,24.8509688,-,11.85830684,7.90803166,33.00362101,34.95734791,39.71621362,31.30174113,35.42144263,46.17419025,39.25496379,64.96621834,61.42513528,67.63676839,82.94154173,63.75546979,37.35321359,32.94496282,63.50642309,76.25818158,68.84454309,75.64623668,73.66316263,83.82499974,60.87034937,71.14413012,65.06991859,105.2171822,109.1412657,77.81356018,64.80085503,96.19592885,84.65875563,110.6558599,53.4821068,31.63658269,30.65953043,12.18085471,16.87145772';

        $table = array_map(function ($value) {
            return explode(',', $value);
        }, explode(';', $data));


        $objSheet->mergeCells('A1:F1');
        $objSheet->setCellValue('A1', $fileName);
        $objSheet->fromArray($detailParams, NULL, 'A4');
        $objSheet->fromArray($table, NULL, 'A11');

        $styles = $this->getExcelStyle();
        $rows_cnt = count($table);
        $cols_cnt = count($table[0]);
        $objSheet->getStyle('A1')->applyFromArray($styles['title']);
        $rows_idx = count($detailParams) + 3;
        $objSheet->getStyle("A4:A{$rows_idx}")->getFont()->setSize(12)->setBold(true);
        $rows_index = $rows_cnt + 10;
        $objSheet->getStyle("A11:AW{$rows_index}")->applyFromArray($styles['content']);
        $objSheet->getStyle("A11:A{$rows_index}")->applyFromArray($styles['header']);
        $objSheet->getStyle("A11:AW11")->applyFromArray($styles['header']);

        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

        header('Content-Type: application/x-xls;');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$fileName);
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
}
