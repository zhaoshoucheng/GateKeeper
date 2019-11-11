<?php

/********************************************
 * # desc:    gift公共类
 * # author:  ningxiangbing@didichuxing.com
 * # date:    2018-08-25
 ********************************************/
class Gift_model extends CI_Model
{
    /**
     * @var CI_DB_query_builder
     */
    private $db;

    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);

        $this->load->config('nconf');
    }

    /**
     * 批量获取resourceUrl
     *
     * @param $resourceKeys array  最多5个
     * @param $namespace    string
     *
     * @return array
     */
    public function getResourceUrlList($resourceKeys, $namespace)
    {
        $chunkResult = array_chunk($resourceKeys, 1);
        $result      = [];
        foreach ($chunkResult as $partKeys) {
            if (empty($result)) {
                $result = $this->_getResourceUrlList($partKeys, $namespace);
            } else {
                $partResult = $this->_getResourceUrlList($partKeys, $namespace);

                $result[$namespace] = array_merge($result[$namespace], $partResult[$namespace]);
            }
        }
        return !empty($result[$namespace]) ? $result[$namespace] : [];
    }

    /**
     * 批量获取resourceUrl
     *
     * @param $resourceKeys array  最多5个
     * @param $namespace    string
     *
     * @return array
     */
    private function _getResourceUrlList($resourceKeys, $namespace)
    {
        $result = [];
        $nconf  = $this->config->item('gift');
        $url    = sprintf("%s?keys=%s", $nconf['batch'][$namespace], implode(',', $resourceKeys));
        $out    = httpGET($url);
        $json   = json_decode($out, true);

        if (!empty($json['result'])) {
            foreach ($json['result'] as $item) {
                $result[$namespace][$item['key']] = $item;
            }
        }
        return $result;
    }

    /**
     * 下载私有key资源
     *
     * @param $resourceKey
     * @param $namespace
     *
     * @return array|void
     * @throws Exception
     */
    public function downPrivateResource($resourceKey, $namespace)
    {
        $itemInfo = $this->getResourceUrlList([$resourceKey], $namespace);

        $url   = $itemInfo[$resourceKey]['download_url'];

        if (empty($itemInfo[$resourceKey])) {
            throw new \Exception("The key source not have.");
        }

        $sUrl    = $url;
        $tPath   = '/tmp/' . $resourceKey;
        $content = file_get_contents($sUrl);
        file_put_contents($tPath, $content);
        $file_filesize = filesize($tPath);
        $file          = fopen($tPath, "r");

        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . $file_filesize);
        Header("Content-Disposition: attachment; filename=" . $resourceKey);
        echo fread($file, $file_filesize);
        fclose($file);
        exit;
    }

    /**
     * 下载key资源
     *
     * @param $resourceKey
     * @param $namespace
     *
     * @return array|void
     * @throws Exception
     */
    public function downResource($resourceKey, $namespace)
    {
        $nconf = $this->config->item('gift');
        $url   = sprintf("%s/%s", $nconf['get'][$namespace], $resourceKey);
        $out   = httpGET($url);

        $result = $this->formatGet($resourceKey, $url, $out);

        if (empty($result['url'])) {
            throw new \Exception("The key source not have.");
        }

        $sUrl    = $result['url'];
        $tPath   = '/tmp/' . $resourceKey;
        $content = file_get_contents($sUrl);
        file_put_contents($tPath, $content);
        $file_filesize = filesize($tPath);
        $file          = fopen($tPath, "r");

        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . $file_filesize);
        Header("Content-Disposition: attachment; filename=" . $resourceKey);
        echo fread($file, $file_filesize);
        fclose($file);
        exit;
    }

    /**
     * 输出格式化
     *
     * @param $resourceKey string 文件名
     * @param $command     string input
     * @param $out         string output
     *
     * @return array
     */
    public function formatGet($resourceKey, $command, $out)
    {
        $output = json_decode($out, true);
        if (!empty($output['download_url']) && !empty($output['md5'])) {
            return [
                "resource_key" => $resourceKey,
                "url" => $output['download_url'],
            ];
        }
        com_log_warning('_itstool_' . __CLASS__ . '_' . __FUNCTION__ . '_uploadError', 0, "uploadError", compact("command", "output"));
        return [];
    }

    function download_remote_file_with_fopen($file_url, $save_to)
    {
        $in  = fopen($file_url, "rb");
        $out = fopen($save_to, "wb");
        while ($chunk = fread($in, 8192)) {
            fwrite($out, $chunk, 8192);
        }
        fclose($in);
        fclose($out);
    }

    /**
     * 根据md5获取url
     *
     * @param $resourceKey
     * @param $namespace
     *
     * @return array
     */
    public function findResourceUrl($resourceKey, $namespace)
    {
        $result = [];
        $nconf  = $this->config->item('gift');
        foreach ($nconf['get'] as $namespace => $gconf) {
            $url = sprintf("%s/%s", $gconf, $resourceKey);
            $out = httpGET($url);

            $result[$namespace] = $this->formatGet($resourceKey, $url, $out);
        }
        return $result;
    }

    /**
     * post文件上传
     *
     * @param $field string 上传文件的key
     *
     * @return array
     * @throws Exception
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
        $allowedExts = ["gif", "jpeg", "jpg", "png", "pdf"];
        $temp        = explode(".", $_FILES[$field]["name"]);
        $extension   = end($temp);
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

        $result   = [];
        $nconf    = $this->config->item('gift');
        $fileName = date("YmdHis") . mt_rand(1000, 9999) . "." . $extension;
        foreach ($nconf['upload'] as $namespace => $uconf) {
            $publicOut = [];
            //参数强制校验,防止任意代码执行
            if (!preg_match('/[\/\d\s]+/ims', $_FILES[$field]["tmp_name"])) {
                throw new \Exception("tmp_name invalid.");
            }
            $commandLine = sprintf("curl %s/%s -X POST -F filecontent=@%s", $uconf, $fileName, $_FILES[$field]["tmp_name"]);
            exec($commandLine, $publicOut);
            $result[$namespace] = $this->formatUpload($fileName, $commandLine, $publicOut);
        }
        return $result;
    }

    /**
     * 输出格式化
     *
     * @param $resourceKey string 文件名
     * @param $command     string input
     * @param $out         array output
     *
     * @return array
     */
    public function formatUpload($resourceKey, $command, $out)
    {
        $json   = isset($out[0]) ? $out[0] : "";
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
