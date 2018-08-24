<?php

/********************************************
 * # desc:    路口数据模型
 * # author:  ningxiangbing@didichuxing.com
 * # date:    2018-03-05
 ********************************************/
class Gift_model extends CI_Model
{
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
        $this->load->config('nconf');
    }

    /**
     * 根据md5获取url
     *
     * @param $md5
     * @param $namespace
     * @return array|void
     */
    public function getResourceUrl($resourceKey, $namespace)
    {
        $nconf = $this->config->item('gift');
        $url = sprintf("%s%s", $nconf[$namespace], $resourceKey);
        $out = httpGET($url);
        return $this->formatOut($resourceKey, $url, $out);
    }

    /**
     * post文件上传
     * @param $field string 上传文件的key
     * @return array|void
     */
    public function upload($field)
    {
        //验证是否上传文件
        if (!isset($_FILES[$field])) {
            throw new \Exception("upload file not exists.");
        }
        $fileInfo = [
            "name" => $_FILES[$field]["name"],
            "type" => $_FILES[$field]["type"],
            "size" => ($_FILES[$field]["size"] / 1024) . "k",
        ];
        com_log_notice('_itstool_' . __CLASS__ . '_' . __FUNCTION__ . '_fileinfo', compact("params", "fileInfo"));

        //参数验证
        $params = $this->input->post();
        $allowedExts = array("gif", "jpeg", "jpg", "png", "pdf");
        $temp = explode(".", $_FILES[$field]["name"]);
        $extension = end($temp);
        $fileTypeArr = ["image/gif", "image/jpeg", "image/jpg", "image/pjpeg", "image/png", "application/pdf"];
        if (!in_array($_FILES[$field]["type"], $fileTypeArr) || !in_array($extension, $allowedExts)) {
            throw new \Exception("not support file_type.");
        }
        if ($_FILES[$field]["size"] >= 1024 * 1024 * 50) {
            throw new \Exception("file size limit 50m.");
        }
        if ($_FILES[$field]["error"] > 0) {
            throw new \Exception($_FILES[$field]["error"]);
        }

        $result = [];
        $nconf = $this->config->item('gift');
        $fileName = date("YmdHis") . mt_rand(1000, 9999) . "." . $extension;
        $commandLine = sprintf("curl %s%s -X POST -F filecontent=@%s", $nconf['itstool_public'], $fileName, $_FILES[$field]["tmp_name"]);
        exec($commandLine, $publicOut);
        $result["public"] = $this->formatOut($fileName, $commandLine, $publicOut);

        $commandLine = sprintf("curl %s%s -X POST -F filecontent=@%s", $nconf['itstool_private'], $fileName, $_FILES[$field]["tmp_name"]);
        exec($commandLine, $privateOut);
        $result["private"] = $this->formatOut($fileName, $commandLine, $privateOut);
        return $result;
    }

    /**
     * 输出格式化
     *
     * @param $fileName 文件名
     * @param $command  input
     * @param $out      output
     * @return array
     */
    public function formatOut($resourceKey, $command, $out)
    {
        $json = isset($out[0]) ? $out[0] : "";
        $output = json_decode($json, true);
        if (!empty($output['download_url']) && !empty($output['md5'])) {
            return [
                "resource_key" => $resourceKey,
                "url" => $output['download_url'],
            ];
        }
        com_log_warning('_itstool_' . __CLASS__ . '_' . __FUNCTION__ . '_uploadError', 0, "uploadError", compact("command", "output"));
        return [];
    }
}
