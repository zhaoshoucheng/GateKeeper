<?php

namespace Services;

use Services\ReportService;

class WordreportService extends BaseService{

    public function __construct(){
        parent::__construct();
        $this->load->model('gift_model');
        $this->load->model('wordreport_model');

        $this->reportService = new ReportService();
    }

    public function getUUID(){
        return gen_uuid();
    }

    // word itstool_public
    public function downReport($params){
        $info = $this->wordreport_model->queryWordReportByID($params['id']);
        if (!empty($info)) {
            $this->gift_model->downPrivateResource($info[0]['file_path'], 'itstool_public',$info[0]['title']."doc");
        }
    }

    // word & pdf list
    public function getReportList($params) {
        $pageNum = $params['page_no'];
        $pageSize = $params['page_size'];
        $params['page_no'] = 1;
        $params['page_size'] = $pageNum * $pageSize;

        $pdf_data = $this->reportService->getReportList($params);
        $pdf_data['list'] = array_map(function ($item) {
            return [
                'title' => $item['title'],
                'time_range' => $item['time_range'],
                'url' => $item['url'],
                'down_url' => str_replace('Wordreport', 'Report', $item['down_url']),
                'doc_type' => 'pdf',
                'create_at' => $item['create_at'],
            ];
        }, $pdf_data['list']);

        $word_data = $this->getWordReportList($params);
        $word_data['list'] = array_map(function ($item) {
            return [
                'title' => $item['title'],
                'time_range' => $item['time_range'],
                'url' => '',
                'down_url' => $item['down_url'],
                'doc_type' => 'word',
                'create_at' => $item['create_at'],
            ];
        }, $word_data['list']);

        $merge_data_list = array_merge($pdf_data['list'], $word_data['list']);
        usort($merge_data_list, function($a, $b) {
            return ($a['create_at'] > $b['create_at']) ? -1 : 1;
        });

        return [
            'list' => array_slice($merge_data_list, ($pageNum - 1) * $pageSize, $pageSize),
            'total' => $pdf_data['total'] + $word_data['total'],
            "page_no" => $pageNum,
            "page_size" => $pageSize,
        ];
    }

    public function getWordReportList($params) {
        $cityId = $params['city_id'];
        $type = $params['type'];
        $pageNum = $params['page_no'];
        $pageSize = $params['page_size'];

        $userapp = $params['userapp'];
        if(isset($params['local']) && $params['local'] == 'jinan'){
            $userapp = 'jinanits';
        }

        $namespace = 'itstool_public';

        $statRow = $this->wordreport_model->getCountUploadFile($cityId, $type, $pageNum, $pageSize);

        $result = $this->wordreport_model->getSelectUploadFile($cityId, $type, $pageNum, $pageSize);
        $formatResult = function ($result) use ($userapp, $statRow, $namespace, $pageNum, $pageSize) {
            $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

            $hostName = $_SERVER['HTTP_HOST'];
            if($_SERVER['REMOTE_ADDR']=="59.52.254.218"){
                $hostName = "59.52.254.216:91";
            }
            if(isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], "/nanjing")){
                $hostName = "sts.didichuxing.com/sg1/api/nanjing";
            }
            if (isset($_SERVER['HTTP_REFERER'])) {
                if ($_SERVER['HTTP_REFERER'] == 'http://test.sts.xiaojukeji.com/signalpro/report') {
                    $hostName    = "test.sts.xiaojukeji.com/sg1/api";
                }
                if ($_SERVER['HTTP_REFERER'] == 'http://test.sts.xiaojukeji.com/signalpro/report') {
                    $hostName    = "sts.didichuxing.com/sg1/api";
                }
            }
            $currentUrl = $protocol . $hostName . $_SERVER['REQUEST_URI'];
            $lastPos    = strrpos($currentUrl, '/');
            $baseUrl    = substr($currentUrl, 0, $lastPos);
            foreach ($result as $key => $item) {
                $result[$key]['down_url'] = $baseUrl . "/Download?id=" . $item["id"];
                if($userapp == "jinanits"){
                    $result[$key]['down_url']  = str_replace("sts.didichuxing.com","172.54.1.214:8088/sg1/api",$result[$key]['down_url']);
                }
            }
            return [
                "list" => $result,
                "total" => $statRow['num'],
                "page_no" => $pageNum,
                "page_size" => $pageSize,
            ];
        };
        return $formatResult($result);
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

        if(isset($params['overview_content_1'])){
            $templateProcessor->cloneBlock("A_BLOCK",1);
            $templateProcessor->setValue("overview_content_1",$params['overview_content_1']);
            $img  = array("path" => $FILES["overview_img_1"]['tmp_watermark'], "width" => 420, "height" => 420);
            $templateProcessor->setImageValue("overview_img_1",$img);
        }else{
            $templateProcessor->cloneBlock("A_BLOCK",0);
        }

        if(isset($params['runningState_content_1'])){
            $templateProcessor->cloneBlock("B_BLOCK",1);
            $templateProcessor->setValue("runningState_content_1",$params['runningState_content_1']);
            $img  = array("path" => $FILES["runningState_chart_1"]['tmp_watermark'], "width" => 420, "height" => 420);
            $templateProcessor->setImageValue("runningState_chart_1",$img);
        }else{
            $templateProcessor->cloneBlock("B_BLOCK",0);
        }

        if(isset($params['runningIndicator_content_1'])){
            $templateProcessor->cloneBlock("C_BLOCK",1);
            $templateProcessor->setValue("runningIndicator_content_1",$params['runningIndicator_content_1']);
            $img  = array("path" => $FILES["runningIndicator_chart_1"]['tmp_watermark'], "width" => 420, "height" => 420);
            $templateProcessor->setImageValue("runningIndicator_chart_1",$img);
            $img['path'] = $FILES["runningIndicator_chart_2"]['tmp_watermark'];
            $templateProcessor->setImageValue("runningIndicator_chart_2",$img);
            $img['path'] = $FILES["runningIndicator_chart_3"]['tmp_watermark'];
            $templateProcessor->setImageValue("runningIndicator_chart_3",$img);
            $img['path'] = $FILES["runningIndicator_chart_4"]['tmp_watermark'];
            $templateProcessor->setImageValue("runningIndicator_chart_4",$img);
        }else{
            $templateProcessor->cloneBlock("C_BLOCK",0);
        }

//        $templateProcessor->cloneBlock("D_BLOCK",0);


        if(isset($params['runningAnalysic_sub_content_1'])){
            $templateProcessor->cloneBlock("D_BLOCK",1);
            $templateProcessor->setValue("runningAnalysic_sub_content_1",$params['runningAnalysic_sub_content_1']);
            $templateProcessor->setValue("runningAnalysic_1_1",$params['runningAnalysic_1_1']);
            $templateProcessor->setValue("runningAnalysic_1_2",$params['runningAnalysic_1_2']);
            $templateProcessor->setValue("runningAnalysic_1_3",$params['runningAnalysic_1_3']);
            $img  = array("path" => $FILES["runningAnalysic_img_1"]['tmp_watermark'], "width" => 420, "height" => 420);
            $templateProcessor->setImageValue("runningAnalysic_img_1",$img);

            //按照规律循环添加,多余的置空
            for ($i=1;$i<5;$i++){
                for ($j=1;$j<=20;$j++){
                    $imgName = "runningAnalysic_chart_".$i."_".$j;
                    if(isset($FILES[$imgName])){
                        $tmpimg  = array("path" => $FILES[$imgName]['tmp_watermark'], "width" => 210, "height" => 210);
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
        //$savePath = sys_get_temp_dir().$fileName.".doc";
//        var_dump($savePath);exit;
//        $templateProcessor->saveAs($savePath);
        return $templateProcessor->save();
//        return $savePath;
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

    public function queryByTaskID($taskID){
        $ret = $this->wordreport_model->queryWordReport($taskID);

        if(empty($ret)){
            throw new \Exception('Unregistered taskID',ERR_DEFAULT);
        }
        return $ret[0];

    }


    //格式化前端上送的数据为模板对应的格式
    public function formartJuncImgKeyValue($params){
        $newParams=[];
        foreach ($params as $pk => $pv){
            if(strpos($pk,"chart")!==false){
                $jsonArr = json_decode($pv,true);
                foreach ($jsonArr['arr'] as $jk => $jv){
                    if(isset($jv['options'][0])){
                        continue;
//                        foreach ($jv['options'] as $ok=>$ov){
//                            $newParams[$pk."_".($jk+1)."_".($ok+1)] = json_encode(array(
//                                'infile'=>$ov['options']
//                            ));
//                        }
                    }else{
                        $newParams[$pk."_".($jk+1)] = json_encode(array(
                            'infile'=>$jv['options']
                        ));
                    }
                }

            }
            if (strpos($pk,"runningAnalysic")!==false){

                if(strpos($pk,"chart")!==false){
                    $jsonArr = json_decode($pv,true);

                    foreach ($jsonArr['arr'] as $jk => $jv){
                        foreach ($jv['options'] as $ok=>$ov){
                            $newParams["runningAnalysic_chart_".($jk+1)."_".($ok+1)] = json_encode(array(
                                'infile'=>$ov['options']
                            ));
                        }
                    }
                }else{
                    $jsonArr = json_decode($pv,true);
                    if($jsonArr){
                        foreach ($jsonArr['arr'] as $jk => $jv){
                            $newParams[$pk."_".($jk+1)] = $jv;
                        }
                    }else{
                        $newParams[$pk] = $pv;
                    }

                }
//                foreach ($pv as $rk => $rv){
//                    $newParams["runningAnalysic_content_".($rk+1)] = $rv['runningAnalysic_content'];
//                    $newParams["runningAnalysic_img_".($rk+1)] = $rv['runningAnalysic_img'];
//                    foreach ($rv['runningAnalysic_sub_content'] as $rck => $rcv){
//                        $newParams["runningAnalysic_sub_content_".($rk+1)."_".($rck+1)] = $rcv;
//                    }
//                }
            }elseif(strpos($pk,"chart")===false){
                $newParams[$pk] = $pv;
            }


        }


        return $newParams;

    }
    //生成临时图表文件
    public function generateChartImg($params,$content){

        $files = [];

        foreach ($params as $pk => $pv){

            if(strpos($pk,"chart")!==false){
                //下载图片
                $filePath = $this->wordreport_model->generateChartImg($pv);
//                $files[$pk]['tmp_watermark'] = $filePath;
                //生成水印图片
                $font = 'application/static/ht.TTF';

                $img = imagecreatefromstring(file_get_contents($filePath));
                $red = imagecolorallocatealpha($img,220, 220, 220,100);
                $font_angle = 30;
                $font_size = 30;
                $img_array = getimagesize($filePath);
                $img_width = $img_array[0];
                $img_height =$img_array[1];
                for ($i=0;$i<=$img_width;$i+=350){
                    for ($j=0;$j<=$img_height;$j+=200){
                        imagettftext($img, $font_size, $font_angle, $i, $j, $red, $font, $content);
                    }
                }
                $files[$pk]=[];
                $newFile = tempnam(sys_get_temp_dir(), 'watermark');
                $files[$pk]['tmp_watermark']=$newFile;

                imagejpeg($img,$newFile);
                $files[$pk]['tmp_watermark'] = $newFile;
                imagedestroy($img);

            }
        }
        return $files;

    }
}