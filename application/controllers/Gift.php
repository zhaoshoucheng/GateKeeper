<?php
/***************************************************************
# git文件上传类
# user:niuyufu@didichuxing.com
# date:2018-08-21
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Gift extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('junction_model');
        $this->load->model('timing_model');
        $this->load->config('nconf');
    }

    public function Upload()
    {
        $params = $this->input->post();

        // 允许上传的图片后缀
        $allowedExts = array("gif", "jpeg", "jpg", "png", "pdf");
        $temp = explode(".", $_FILES["file"]["name"]);
        $extension = end($temp);
        $fileTypeArr = ["image/gif","image/jpeg","image/jpg","image/pjpeg","image/png","application/pdf"];
        if(!in_array($_FILES["file"]["type"],$fileTypeArr) || !in_array($extension, $allowedExts)){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = "not support file_type.";
            return;
        }
        if($_FILES["file"]["size"] >= 1024*1024*50){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = "file size limit 50m.";
            return;
        }
        if ($_FILES["file"]["error"] > 0)
        {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $_FILES["file"]["error"];
            return;
        }

        echo "上传文件名: " . $_FILES["file"]["name"] . "<br>";
        echo "文件类型: " . $_FILES["file"]["type"] . "<br>";
        echo "文件大小: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
        echo "文件临时存储的位置: " . $_FILES["file"]["tmp_name"] . "<br>";
        //logger

        $fileInfo = [
            "name"=>$_FILES["file"]["name"],
            "type"=>$_FILES["file"]["type"],
            "size"=>($_FILES["file"]["size"] / 1024) . "k",
        ];
        com_log_strace('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, [], compact("params","fileInfo"));
        exit;
    }
}
