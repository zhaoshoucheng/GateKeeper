<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 17/7/20
 * Time: 11:20
 */

if (!function_exists('httpGET')) {
    function httpGET($url, $query=array(), $msTimeout = 20000, $headers = array()){
        //初始化参数
        $originUrl = $url;
        if(is_array($query) && count($query)>0){
            $url = sprintf("%s?%s", $url, http_build_query($query));
        }elseif (!empty($query)){
            $url = sprintf("%s?%s", $url, $query);
        }
        $cSpanId = gen_span_id();
        $traceId = get_traceid();
        $headers = array_merge($headers, [
            'didi-header-rid: ' . $traceId,
            'didi-header-spanid: ' . $cSpanId,
        ]);

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
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //设置header

        $timeStart = microtime(true);
        $ret = curl_exec($ch);
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        if($errno = curl_errno($ch)){
            $errmsg = curl_error($ch);
            //记录报警
            com_log_warning("_com_http_failure", $errno, $errmsg, array("cspanid"=>$cSpanId, "url"=>$originUrl, "args"=>http_build_query($query)));
            curl_close($ch);
            return false;
        }
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($responseCode != 200){
            //临时自适应接口不报警
            $ignoreUrl = '100.70.160.62:8000';
            if(strpos($url, $ignoreUrl)!==false){
                return false;
            }
            com_log_warning("_com_http_failure", $responseCode, "", array("cspanid"=>$cSpanId, "url"=>$originUrl, "args"=>http_build_query($query)));
            return false;
        }

        com_log_notice('_com_http_success', ["cspanid"=>$cSpanId, "url"=>$url, "args"=>http_build_query($query), "response"=>substr($ret,0,10*1024), "errno"=>$responseCode, 'proc_time'=> $totalTime]);
        return $ret; 
    }
}


if (!function_exists('httpPOST')) {
    function httpPOST($url, $data, $msTimeout = 0, $contentType='x-www-form-urlencoded', $headers = array()){
        $cSpanId = gen_span_id();
        $traceId = get_traceid();
        $headers = array_merge($headers, [
            'didi-header-rid: ' . $traceId,
            'didi-header-spanid: ' . $cSpanId,
        ]);

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
            $headers = array_merge($headers, [
                'Content-Type: application/json', 'Content-Length: ' . strlen($data),
            ]);
        } elseif ($contentType == 'raw') {
            $data = $data;
        } else  {
            $data = http_build_query($data);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //设置header
        $timeStart = microtime(true);
        $ret = curl_exec($ch);
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        
        if($errno = curl_errno($ch)){
            $errmsg = curl_error($ch);

            com_log_warning("_com_http_failure", $errno, $errmsg, array("cspanid"=>$cSpanId, "url"=>$url, "args"=>$data));

            curl_close($ch);
            return false;
        }
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($responseCode != 200){
            //临时自适应接口不报警
            $ignoreUrl = '100.70.160.62:8000';
            if(strpos($url, $ignoreUrl)!==false){
                return false;
            }
            com_log_warning("_com_http_failure", $responseCode, "", array("cspanid"=>$cSpanId, "url"=>$url, "args"=>$data));
            return false;
        }

        com_log_notice('_com_http_success', ["cspanid"=>$cSpanId, "url"=>$url, "args"=>$data, "response"=>substr($ret,0,10*1024), "errno"=>$responseCode, 'proc_time'=> $totalTime]);
        return $ret;
    }
}
