<?php

namespace Services;

class WordreportService extends BaseService{

    public function __construct(){
        parent::__construct();
        $this->load->model('gift_model');
        $this->load->model('wordreport_model');
    }

    public function getUUID(){
        return gen_uuid();
    }

    public function checkFile($FILES){
        if(count($FILES) == 0 ){
            return;
        }
        foreach ($FILES as $k => $f){
            switch ($f['error'])
            {
                case 1:
                    throw new \Exception($k.'上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',ERR_DEFAULT);
                    break;
                case 2:
                    throw new \Exception($k.'上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',ERR_DEFAULT);
                    break;
                case 3:
                    throw new \Exception($k.'文件只有部分被上传',ERR_DEFAULT);
                    break;
                case 4:
                    throw new \Exception($k.'没有文件被上传',ERR_DEFAULT);
                    break;
                case 5:
                    throw new \Exception($k.'上传文件大小为0',ERR_DEFAULT);
                    break;
            }
        }
    }

    public function addWatermark($FILES,$content){
        //添加水印
        foreach ($FILES as $k => $f){

            $file =$f['tmp_name'];
            $font = 'application/static/ht.TTF';
            $img = imagecreatefromstring(file_get_contents($file));

            $red = imagecolorallocatealpha($img,220, 220, 220,100);

            $font_angle = 30;
            $font_size = 30;
            $img_array = getimagesize($file);
            $img_width = $img_array[0];
            $img_height =$img_array[1];

            for ($i=0;$i<=$img_width;$i+=350){
                for ($j=0;$j<=$img_height;$j+=200){
                    imagettftext($img, $font_size, $font_angle, $i, $j, $red, $font, $content);
                }
            }

            $newFile = $f['tmp_name'].$f['name'];
            imagejpeg($img,$newFile);
            $FILES[$k]['tmp_watermark'] = $newFile;
            imagedestroy($img);

        }
        return $FILES;
    }

    public function clearWatermark($FILES){
        foreach ($FILES as $k => $f){
            if(file_exists($f['tmp_watermark'])){
                unlink($f['tmp_watermark']);
            }
        }
    }

    public function createJuncDoc($params,$FILES){
//        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('application/static/junc_template.docx');
        $templateProcessor = new TemplateProcessorMod('application/static/junc_template.docx');
        $templateProcessor->setValue('title',$params['title']);
        $templateProcessor->setValue('subtitle1',$params['subtitle1']);
        $templateProcessor->setValue('subtitle2',$params['subtitle2']);

        if(isset($params['content1_1'])){
            $templateProcessor->cloneBlock("A_BLOCK",1);
            $templateProcessor->setValue("content1_1",$params['content1_1']);
            $img  = array("path" => $FILES["img1_1"]['tmp_watermark'], "width" => 450, "height" => 450);
            $templateProcessor->setImageValue("img1_1",$img);
        }else{
            $templateProcessor->cloneBlock("A_BLOCK",0);
        }

        if(isset($params['content2_1'])){
            $templateProcessor->cloneBlock("B_BLOCK",1);
            $templateProcessor->setValue("content2_1",$params['content2_1']);
            $img  = array("path" => $FILES["img2_1"]['tmp_watermark'], "width" => 450, "height" => 450);
            $templateProcessor->setImageValue("img2_1",$img);
        }else{
            $templateProcessor->cloneBlock("B_BLOCK",0);
        }

        if(isset($params['content3_1'])){
            $templateProcessor->cloneBlock("C_BLOCK",1);
            $templateProcessor->setValue("content3_1",$params['content3_1']);
            $img  = array("path" => $FILES["img3_1"]['tmp_watermark'], "width" => 450, "height" => 450);
            $templateProcessor->setImageValue("img3_1",$img);
            $img['path'] = $FILES["img3_2"]['tmp_watermark'];
            $templateProcessor->setImageValue("img3_2",$img);
            $img['path'] = $FILES["img3_3"]['tmp_watermark'];
            $templateProcessor->setImageValue("img3_3",$img);
        }else{
            $templateProcessor->cloneBlock("C_BLOCK",0);
        }

        if(isset($params['content4_1'])){
            $templateProcessor->cloneBlock("D_BLOCK",1);
            $templateProcessor->setValue("listing4_1",$params['listing4_1']);
            $templateProcessor->setValue("content4_1",$params['content4_1']);
            $templateProcessor->setValue("content4_2",$params['content4_2']);
            $templateProcessor->setValue("content4_3",$params['content4_3']);
            $templateProcessor->setValue("content4_4",$params['content4_4']);
            $img  = array("path" => $FILES["img4_1"]['tmp_watermark'], "width" => 450, "height" => 450);
            $templateProcessor->setImageValue("img4_1",$img);

            //按照规律循环添加,多余的置空
            for ($i=0;$i<5;$i++){
                for ($j=0;$j<20;$j++){
                    $imgName = "img4_".$i."_".$j;
                    if(isset($FILES[$imgName])){
                        $tmpimg  = array("path" => $FILES[$imgName]['tmp_watermark'], "width" => 450, "height" => 450);
                        $templateProcessor->setImageValue($imgName,$tmpimg);
                    }else{
                        $templateProcessor->setValue($imgName,"");
                    }
                }
            }
        }else{
            $templateProcessor->cloneBlock("D_BLOCK",0);
        }



//        $file = $params['title'].'.docx';
//        header("Content-Description: File Transfer");
//        header('Content-Disposition: attachment; filename="' . $file . '"');
//        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
//        header('Content-Transfer-Encoding: binary');
//        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
//        header('Expires: 0');
//        $templateProcessor->saveAs("php://output");
        return $templateProcessor->save();
    }

    /*
     * 保存word文件至gift
     * */
    public function saveDoc($docPath){
        $docName = $fileName = date("YmdHis") . mt_rand(1000, 9999) . "." . "doc";
        $ret = $this->gift_model->uploadDoc($docName,$docPath);
        return $ret;
    }

    /*
     * 创建任务
     * */
    public function createTask($taskID,$params){

        $ret = $this->wordreport_model->queryWordReport($taskID);
        if(count($ret) >0){
            return;
        }

        $this->wordreport_model->createWordReport($params);

    }

    /*
     * 更新任务
     * */
    public function updateTask($taskID,$filePath,$status){
        $this->wordreport_model->updateWordReport($taskID,$filePath,$status);
    }

    public function checkTaskID($taskID){
        $ret = $this->wordreport_model->queryWordReport($taskID);
        if(empty($ret)){
            throw new \Exception('Unregistered taskID',ERR_DEFAULT);
        }

    }
}