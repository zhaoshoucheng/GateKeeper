<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2020/2/4
 * Time: 下午3:09
 */

class Wordreport extends CI_Controller{

    public function __construct()
    {
        parent::__construct();

    }

//    public function TemplateWord(){
//        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('./template.docx');
//        $templateProcessor->setValue('title1','测试题目1');
//        $templateProcessor->setValue('answer1','测试答案1');
//        $templateProcessor->setValue('title2','测试题目2');
//        $templateProcessor->setValue('answer2','测试答案2');
//        $img  = array("path" => './123.jpg', "width" => 300, "height" => 300);
//        $templateProcessor->setImageValue('img1',$img);
//        $templateProcessor->setValue('img2',"");
//        $templateProcessor->deleteBlock("img3");
//        $templateProcessor->cloneBlock("img1",1,false);
//
//
//        $file = '中文test.docx';
//        header("Content-Description: File Transfer");
//        header('Content-Disposition: attachment; filename="' . $file . '"');
//        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
//        header('Content-Transfer-Encoding: binary');
//        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
//        header('Expires: 0');
//        $templateProcessor->saveAs("php://output");
//        exit;
//    }
//
//    public function TextWord(){
//        $title = "test";
//        $num = 1;
//        $mean = 1;
//        $phpWord = new \PhpOffice\PhpWord\PhpWord();
//
//        $section = $phpWord->addSection();
//        $phpWord->addTitleStyle(2, array('bold' => true, 'size' => 14, 'name' => 'Arial', 'Color' => '333'), array('align' => 'center'));
//        $section->addTitle("$title", 2);
//        $section->addTextBreak(1);
//        $section->addText("姓名：题量： 1：");
//        $tableStyle = array(
//            'borderSize' => 6,
//            'borderColor' => '006699'
//        );
//        $table = $section->addTable($tableStyle);
//        $fancyTableCellStyle = array('valign' => 'center');
//        $cellRowSpan = array('vMerge' => 'restart', 'valign' => 'center');
//        $cellRowContinue = array('vMerge' => 'continue');
//        $fontStyle['name'] = 'Arial';
//        $fontStyle['size'] = 14;
//        $thStyle['name'] = 'Arial';
//        $thStyle['size'] = 12;
//        $thStyle['bold'] = true;
//        $paraStyle['align'] = 'center';
//        $table->addRow(500);
//        $table->addCell(3500, $fancyTableCellStyle)->addText('答题区', $thStyle, $paraStyle);
//        $table->addCell(1000, $fancyTableCellStyle)->addText('批改区', $thStyle, $paraStyle);
//        $table->addCell(3500, $fancyTableCellStyle)->addText('答题区', $thStyle, $paraStyle);
//        $table->addCell(1000, $fancyTableCellStyle)->addText('批改区', $thStyle, $paraStyle);
//        $len = ceil($num / 2);
//        for ($i = 0; $i < $len; $i++) {
//            $table->addRow(500);
//            $table->addCell(3500, $fancyTableCellStyle)->addText(($i * 2 + 1) . '.' . $mean[$i * 2], $fontStyle);
//            $table->addCell(1000, $cellRowSpan)->addText(' ');
//            if ($num % 2 != 0 && $i == $len - 1) {
//                $table->addCell(3500, $fancyTableCellStyle)->addText('');
//            } else {
//                $table->addCell(3500, $fancyTableCellStyle)->addText(($i * 2 + 2) . '.' . $mean[$i * 2 + 1], $fontStyle);
//            }
//            $table->addCell(1000, $cellRowSpan)->addText(' ');
//            $table->addRow(1000);
//            $table->addCell(3500, $fancyTableCellStyle)->addText('答案:');
//            $table->addCell(null, $cellRowContinue);
//            if ($num % 2 != 0 && $i == $len - 1) {
//                $table->addCell(3500, $fancyTableCellStyle)->addText('');
//            } else {
//                $table->addCell(3500, $fancyTableCellStyle)->addText('答案:');
//            }
//
//            $table->addCell(null, $cellRowContinue);
//        }
//        $file = './123.jpg';
//        $font = './ht.TTF';
//        $img_array = getimagesize($file);
//        $img_width = $img_array[0];
//        $img_height =$img_array[1];
//        $img = imagecreatefromjpeg($file);
//
//        $red = imagecolorallocatealpha($img, 255, 0, 0,100);
//
//        $font_angle = 20;
//        $font_size = 40;
//        $x = 100;
//        $y = 180;
//        $str = "智慧交通";
//        imagettftext($img, $font_size, $font_angle, $x, $y, $red, $font, $str);
//        imagettftext($img, $font_size, $font_angle, $x+100, $y+100, $red, $font, $str);
//
//
////        header("content-type:image/jpeg");
//        imagejpeg($img,"./tt.jpg");
//        $section->addImage("./tt.jpg");
//        imagedestroy($img);
//
//        $file = '中文test.docx';
//        header("Content-Description: File Transfer");
//        header('Content-Disposition: attachment; filename="' . $file . '"');
//        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
//        header('Content-Transfer-Encoding: binary');
//        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
//        header('Expires: 0');
//        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
//        $objWriter->save("php://output");
//
//        exit;
//    }
//
//    public function Watermark(){
//        $file = './123.jpg';
//        $font = './s.ttf';
//
//        $im = imagecreatefromstring(file_get_contents($file));
//        $black = imagecolorallocate($im, 255, 255, 255);
//        imagefttext($im, 60, 0, 170, 510, $black, $font, '智慧交通');
//
//
//
//        header("content-type:image/jpeg");
//        imagejpeg($im);
//
//
//        imagedestroy($im);
//    }

    /*
     * 获得唯一ID
     * */
    public function GetUUID(){

    }

    /*
     * 获取报告列表
     * */
    public function GetReportList(){

    }

    /*
     * 报告下载
     * */
    public function Download(){

    }

    /*
     * 创建任务
     * */
    public function CreateTask(){

    }

    /*
     * 创建路口报告word
     * */
    public function CreateJuncDoc(){

    }

    /*
     * 创建干线报告word
     * */
    public function CreateRoadDoc(){

    }

    /*
     * 创建区域报告word
     * */
    public function CreateAreaDoc(){

    }


}