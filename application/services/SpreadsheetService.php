<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2019/12/27
 * Time: 下午6:00
 */

namespace Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SpreadsheetService extends BaseService{

    public function __construct()
    {
        parent::__construct();

        // load model
        $this->load->model('spreadsheet_model');

    }

    public function saveUploadFile($FILES){

        $this->checkUploadFile($FILES);
        $dir = '/tmp/'.iconv('UTF-8','gbk',basename($FILES['file']['name']));
        move_uploaded_file($_FILES["file"]["tmp_name"], $dir);

    }

    public function checkUploadFile($FILES){
        if ($FILES['file']['error'] > 0) {
            switch ($FILES['file']['error'])
            {
                case 1:
                    throw new \Exception('上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',ERR_DEFAULT);
                    break;
                case 2:
                    throw new \Exception('上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',ERR_DEFAULT);
                    break;
                case 3:
                    throw new \Exception('文件只有部分被上传',ERR_DEFAULT);
                    break;
                case 4:
                    throw new \Exception('没有文件被上传',ERR_DEFAULT);
                    break;
                case 5:
                    throw new \Exception('上传文件大小为0',ERR_DEFAULT);
                    break;
            }
        }
    }

    public function SingleTimeOptSpreadsheet($FILE,$Obj){

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();


        $worksheet->setTitle('单点时段优化');
        $worksheet->setCellValueByColumnAndRow(1, 1, '路口id');
        $worksheet->setCellValueByColumnAndRow(2, 1, $Obj['logic_junction_id']);
        $worksheet->setCellValueByColumnAndRow(1, 2, '路口名称');
        $worksheet->setCellValueByColumnAndRow(2, 2, $Obj['junction_name']);
        $worksheet->setCellValueByColumnAndRow(1, 3, '优化时段');
        $worksheet->setCellValueByColumnAndRow(2, 4, '开始时间');
        $worksheet->setCellValueByColumnAndRow(3, 4, '结束时间');

        foreach ($Obj['opt_rets'] as $key => $value){
            $worksheet->setCellValueByColumnAndRow(1,5+$key, '时段'.($key+1));
            $worksheet->setCellValueByColumnAndRow(2,5+$key, $value['start_time']);
            $worksheet->setCellValueByColumnAndRow(3,5+$key, $value['end_time']);
        }



        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setPath($FILE["file"]["tmp_name"]);
        $drawing->setCoordinates('A'.(count($Obj['opt_rets'])+6));
        $drawing->setWorksheet($worksheet);

        $filename = '单点时段优化结果.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function SingleGreenOptSpreadsheet($FILE,$Obj){
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('单点绿信比优化');

        $worksheet->setCellValueByColumnAndRow(1, 1, '路口id');
        $worksheet->setCellValueByColumnAndRow(2, 1, $Obj['logic_junction_id']);
        $worksheet->setCellValueByColumnAndRow(1, 2, '路口名称');
        $worksheet->setCellValueByColumnAndRow(2, 2, $Obj['junction_name']);
        $worksheet->setCellValueByColumnAndRow(1, 3, '时段');
        $worksheet->setCellValueByColumnAndRow(2, 3, $Obj['time']);
        $worksheet->setCellValueByColumnAndRow(1, 4, '周期');
        $worksheet->setCellValueByColumnAndRow(2, 4, $Obj['cycle']);
        $worksheet->setCellValueByColumnAndRow(1, 5, '相位差');
        $worksheet->setCellValueByColumnAndRow(2, 5, $Obj['offset']);
        $worksheet->setCellValueByColumnAndRow(2, 6, '开始时间');
        $worksheet->setCellValueByColumnAndRow(3, 6, '持续时间');
        $worksheet->setCellValueByColumnAndRow(4, 6, '黄灯时间');
        $worksheet->setCellValueByColumnAndRow(5, 6, '结束时间');

        $lineNum = 7;
        foreach ($Obj['opt_rets'] as $key => $value){
            $worksheet->setCellValueByColumnAndRow(1,$lineNum+$key, $value['name']);
            $worksheet->setCellValueByColumnAndRow(2,$lineNum+$key, $value['start_time']);
            $worksheet->setCellValueByColumnAndRow(3,$lineNum+$key, $value['green_length']);
            $worksheet->setCellValueByColumnAndRow(4,$lineNum+$key, $value['yellow']);
            $worksheet->setCellValueByColumnAndRow(5,$lineNum+$key, $value['end_time']);
        }

        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setPath($FILE["file"]["tmp_name"]);
        $drawing->setCoordinates('A'.(count($Obj['opt_rets'])+$lineNum));
        $drawing->setWorksheet($worksheet);


        $filename = '单点绿信比优化.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function RoadTrafficOptSpreadsheet($FILE,$Obj){
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('干线协调优化');

//        $worksheet->setCellValueByColumnAndRow(1, 1, '干线名称');
//        $worksheet->setCellValueByColumnAndRow(2, 1, $Obj['road_name']);

        $line = 2;
        foreach ($Obj['opt_rets'] as $key => $value){
            $worksheet->setCellValueByColumnAndRow(1, $line, '绿波优化方向');
            $worksheet->setCellValueByColumnAndRow(2, $line, $value['direction']);
            $line++;
            $worksheet->setCellValueByColumnAndRow(2, $line, '周期');
            $worksheet->setCellValueByColumnAndRow(3, $line, '相位差');
            $worksheet->setCellValueByColumnAndRow(4, $line, '绿灯开始时间');
            $worksheet->setCellValueByColumnAndRow(5, $line, '绿灯持续时间');
            $line++;
            foreach ($value['junction_info'] as $jinfo){
                $worksheet->setCellValueByColumnAndRow(1, $line, $jinfo['name']);
                $worksheet->setCellValueByColumnAndRow(2, $line, $jinfo['cycle']);
                $worksheet->setCellValueByColumnAndRow(3, $line, $jinfo['offset']);
                $worksheet->setCellValueByColumnAndRow(4, $line, $jinfo['start_time']);
                $worksheet->setCellValueByColumnAndRow(5, $line, $jinfo['green_length']);
                $line++;
            }
        }

        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setPath($FILE["file"]["tmp_name"]);
        $drawing->setCoordinates('A'.($line+1));
        $drawing->setWorksheet($worksheet);


        $filename = '干线协调优化.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

}