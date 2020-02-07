<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2020/2/4
 * Time: 下午3:09
 */

use Services\WordreportService;

class Wordreport extends MY_Controller{

    public function __construct()
    {
        parent::__construct();
        $this->wordreportService = new WordreportService();

    }


    /*
     * 获得唯一ID
     * */
    public function GetUUID(){
        $uuid = $this->wordreportService->getUUID();
        $data = ['uuid' => $uuid];
        $this->response($data);
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

        $params = $this->input->post(null, true);
        $this->validate([
            'city_id'    => 'required|is_natural_no_zero',
            'task_id'    => 'required',
            'title'      => 'required',
            'type'       =>'required',
            'time_range' =>"required",

        ]);
        $params['user_info'] = $this->username;

        $this->wordreportService->createTask($params['task_id'],$params);

        $this->response("success");
    }

    /*
     * 创建路口报告word
     * */
    public function CreateJuncDoc(){

        $params = $this->input->post(null, true);
        $this->validate([
            'city_id'    => 'required|is_natural_no_zero',
            'task_id'    => 'required',
        ]);
        //TODO taskid确认

        $this->wordreportService->checkFile($_FILES);

        //图片添加水印
        $newFiles = $this->wordreportService->addWatermark($_FILES,"滴滴智慧交通");

        //生成word文件
        $docFile = $this->wordreportService->createJuncDoc($params,$newFiles);

        //销毁水印图片
        $this->wordreportService->clearWatermark($newFiles);

        //文件上传至gift
        $ret  = $this->wordreportService->saveDoc($docFile);

        //数据入库
        if(isset($ret['url'])){
            $this->wordreportService->updateTask($params['task_id'],1);
        }else{
            $this->wordreportService->updateTask($params['task_id'],2);
        }


        $this->response($ret['url']);
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