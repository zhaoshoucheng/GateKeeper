<?php
/***************************************************************
# 区域管理
# user:niuyufu@didichuxing.com
# date:2018-08-23
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Area extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('area_model');
        $this->load->config('evaluate_conf');
        $this->load->config('readtime_conf');
    }

    /**
     * v2
     * 添加区域
     */
    public function addAreaWithJunction()
    {
        $params = $this->input->post(null,true);
        $validate = Validate::make($params, [
            'area_name' => 'min:1',
            'city_id' => 'min:1',
            'junction_ids' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $params['junction_ids'] = !empty($params['junction_ids']) ? $params['junction_ids'] : [];
            $data = $this->area_model->addAreaWithJunction([
                'area_name' => $params["area_name"],
                'city_id' => intval($params['city_id']),
                'junction_ids' => $params['junction_ids'],
            ]);
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }

    /**
     * v2
     * 更新区域及路口
     */
    public function updateAreaWithJunction()
    {
        $params = $this->input->post(null,true);
        $validate = Validate::make($params, [
            'area_id' => 'min:1',
            'area_name' => 'min:1',
            'junction_ids' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $params['junction_ids'] = !empty($params['junction_ids']) ? $params['junction_ids'] : [];
            $data = $this->area_model->updateAreaWithJunction([
                'area_id' => intval($params['area_id']),
                'area_name' => $params["area_name"],
                'junction_ids' => $params['junction_ids'],
            ]);
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }

    /**
     * 区域删除
     */
    public function delete()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'area_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $data = $this->area_model->delete(intval($params['area_id']));
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }

    /**
     * 区域列表
     */
    public function getList()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'city_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $data = $this->area_model->getList(intval($params['city_id']));
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response(["list"=>$data]);
    }

    /**
     * v2
     * 删除区域路口
     */
    public function deleteJunction()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'area_id' => 'min:1',
            'logic_junction_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $data = $this->area_model->updateAreaJunction($params['area_id'], $params['logic_junction_id'], 2);
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }

    /**
     * v2
     * 区域路口列表
     */
    public function getAreaJunctionList()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            //'city_id' => 'min:1',
            'area_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $params['city_id'] = !empty($params['city_id']) ? $params['city_id'] : 0;
            $data = $this->area_model->getAreaJunctionList(intval($params['city_id']), $params['area_id']);
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }

    /**
     * 获取城市全部区域的详细信息
     */
    public function getAllAreaJunctionList()
    {
        $params = $this->input->post();

        $validator = Validator::make($params, [
            'city_id' => 'required',
        ]);

        if($validator->fail()) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validator->firstError();
            return;
        }

        //异常处理
        try {
            $data = $this->area_model->getAllAreaJunctionList($params);
            return $this->response($data);
        } catch (Exception $e) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $e->getMessage();
            return;
        }
    }

    /**
     * 获取区域评估指标
     */
    public function getQuotas()
    {
        $this->response($this->config->item('area'));
    }

    /**
     * 区域评估
     */
    public function comparison()
    {
        $params = $this->input->post();

        //数据校验
        $validator = Validator::make($params, [
            'city_id' => 'required;numeric',
            'area_id' => 'required;numeric',
            'quota_key' => 'required',
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

        //异常处理
        try {
            $data = $this->area_model->comparison($params);



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
            'download_url' => '/api/area/download?download_id='. $params['download_id']
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
            $detailParams[] = ['评估时间'.($key+1), implode(' ~ ', $item)];
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
            $cols_cnt = count($table[0]) - 1;
            $rows_index = $rows_cnt + $line - 1;

            $objSheet->getStyle("A{$line}:".$this->intToChr($cols_cnt) . $rows_index)->applyFromArray($styles['content']);
            $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($styles['header']);
            $objSheet->getStyle("A{$line}:".$this->intToChr($cols_cnt) . $line)->applyFromArray($styles['header']);

            $line += ($rows_cnt + 2);
        }

        if(!empty($data['evaluate'])) {

            foreach ($data['evaluate'] as $datum) {
                $table = $this->getExcelArray($datum);

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
}
