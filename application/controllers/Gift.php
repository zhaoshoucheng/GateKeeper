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

        $fileInfo = [
            "name"=>$_FILES["file"]["name"],
            "type"=>$_FILES["file"]["type"],
            "size"=>($_FILES["file"]["size"] / 1024) . "k",
        ];
        com_log_notice('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_fileinfo', compact("params"));
        exec("curl http://10.90.28.42:8050/resource/anything/test.jpg -X POST -F filecontent=@license.txt",$output);
        exec("curl http://10.90.28.42:8050/resource/anything/test.jpg -X POST -F filecontent=@license.txt",$output);
        print_r($output);exit;
    }


    /**
     * Http请求工具
     * @param string $url 请求URL
     * @param array $params 请求参数
     * @param string $method 请求方法GET/POST
     * @param int $port 请求端口
     * @param array $header 请求头
     * @param bool|false $multi 是否为文件类型
     * @param array $curlInfo 是否为文件类型
     * @return mixed 响应数据
     * @throws Exception
     */
    function http($url, $params, $method = 'GET' ,&$curlInfo = array(),$header = array(), $multi = false, $port = 80){
        $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => $header
        );
        /* 根据请求类型设置特定参数 */
        switch(strtoupper($method)){
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                if($multi)
                {
                    if(is_array($params))
                    {
                        $k = array_keys($params)[0];
                        $v = $params[$k];
                        $params[$k] = new \CURLFile(realpath($v));
                    }
                }
                else
                {
                    $params = http_build_query($params);
                }
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                $opts[CURLOPT_PORT] = $port;
                break;
            default:
                exit('不支持的请求方式！');
            //throw new Exception();
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);
        if($error)exit('请求发生错误：' . $error);
        //throw new Exception();
        return  $data;
    }
}
