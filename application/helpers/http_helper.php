<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 17/7/20
 * Time: 11:20
 */

if (!function_exists('httpGET')) {
    function httpGET($url, $query=array(), $msTimeout = 20000, $headers = array()){

        $spanId = gen_span_id();

        $path = parse_url($url, PHP_URL_PATH);
        $originUrl = $url;

        if(is_array($query) && count($query)>0){
            $url = sprintf("%s?%s", $url, http_build_query($query));
        }elseif (!empty($query)){
            $url = sprintf("%s?%s", $url, $query);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($msTimeout > 0) {
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $msTimeout);
        } else {
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $timeStart = microtime(true);

        $ret = curl_exec($ch);

        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;

        if($errno = curl_errno($ch)){
            $errmsg = curl_error($ch);

            com_log_warning("_com_http_failure", $errno, $errmsg, array("cspanid"=>$spanId, "url"=>$originUrl, "args"=>$query));

            curl_close($ch);
            return false;
        }
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($responseCode != 200){
            com_log_warning("_com_http_failure", $responseCode, "", array("cspanid"=>$spanId, "url"=>$originUrl, "args"=>$query));
            return false;
        }

        com_log_notice('_com_http_success', ["cspanid"=>$spanId, "url"=>$url, "args"=>$query, "response"=>$ret, "errno"=>$responseCode, 'proc_time'=> $totalTime]);
        return $ret;
    }
}


if (!function_exists('httpPOST')) {
    function httpPOST($url, $data, $msTimeout = 0, $contentType='x-www-form-urlencoded'){

        $spanId = gen_span_id();

        $path = parse_url($url, PHP_URL_PATH);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_ENCODING, "UTF-8");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if ($msTimeout > 0) {
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $msTimeout);
        } else {
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        if($contentType == 'json'){
            $data = json_encode($data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
        }else{
            $data = http_build_query($data);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $timeStart = microtime(true);

        $ret = curl_exec($ch);

        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;

        if($errno = curl_errno($ch)){
            $errmsg = curl_error($ch);

            com_log_warning("_com_http_failure", $errno, $errmsg, array("cspanid"=>$spanId, "url"=>$url, "args"=>$data));

            curl_close($ch);
            return false;
        }
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($responseCode != 200){
            com_log_warning("_com_http_failure", $responseCode, "", array("cspanid"=>$spanId, "url"=>$url, "args"=>$data));
            return false;
        }

        com_log_notice('_com_http_success', ["cspanid"=>$spanId, "url"=>$url, "args"=>$data, "errno"=>$responseCode, 'proc_time'=> $totalTime]);
        return $ret;
    }
}
