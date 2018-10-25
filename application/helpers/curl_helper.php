<?php
/**
 * 封装curl，默认使用post格式
 *
 * @param mixed $url     请求的url
 * @param mixed $params  当方法为post的时候，传入的postfield的参数
 * @param mixed $options 传入的curl_opt字段。 其中默认调用方法为post CURLOPT_POST=1, 将请求的数据直接返回CURLOPT_RETURNTRANSFER=1, 超时时间为5秒
 *                       CURLOPT_TIMEOUT=5, 发起连接前等待的时间1秒 CURLOPT_CONNECTTIMEOUT=1
 * @param mixed $caller  调用方
 *
 * @return array
 * @author  AndyCong<congming@diditaxi.com.cn>
 * @version 2014-05-21
 */

function curl($url, $params = [], $options = [], $retries = 2, $connectTime = 1, $caller = '', $aExtInfo = [])
{ // retries 重试次数, 默认为2
    if ($caller) {
        $result = \xiaoju\sdk\http\gs\SdkHttpGs::curl($url, $params, $options, $retries, $connectTime);
    } else {
        global $_global_span_id;
        $curlInstance = curl_init($url);
        $defaultOpt   = [
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_CONNECTTIMEOUT => intval($connectTime),
        ];
        if (is_array($options) && !empty($options)) {
            foreach ($options as $k => $v) {
                $defaultOpt[$k] = $v;
            }
        }
        if (!isset($defaultOpt[CURLOPT_HTTPHEADER])) {
            $defaultOpt[CURLOPT_HTTPHEADER] = [];
        }
        $traceId = isset($_SERVER['HTTP_DIDI_HEADER_RID']) ? strval($_SERVER['HTTP_DIDI_HEADER_RID']) : '';
        if (!empty($traceId)) {
            $defaultOpt[CURLOPT_HTTPHEADER][] = 'didi-header-rid: ' . $traceId;
        }

        //add span id
        if (!empty($GLOBALS['cspanId'])) {
            $spanId = $GLOBALS['cspanId'];
        } else {
            if (function_exists("gen_span_id")) {
                $spanId = gen_span_id();
            } else {
                $spanId = isset($_global_span_id) ? strval($_global_span_id) : '';
            }
        }
        if (!empty($spanId)) {
            $sSpanHeader                      = 'didi-header-spanid: ' . $spanId;
            $defaultOpt[CURLOPT_HTTPHEADER][] = $sSpanHeader;
            $sSpanKey                         = array_search($sSpanHeader, $defaultOpt[CURLOPT_HTTPHEADER]);
        }

        //add hint code & hint content
        $hintCode = isset($_SERVER['HTTP_DIDI_HEADER_HINT_CODE']) ? strval($_SERVER['HTTP_DIDI_HEADER_HINT_CODE']) : '';
        if (!empty($hintCode)) {
            $defaultOpt[CURLOPT_HTTPHEADER][] = 'didi-header-hint-code: ' . $hintCode;
        }
        $hintContent = isset($_SERVER['HTTP_DIDI_HEADER_HINT_CONTENT']) ? strval($_SERVER['HTTP_DIDI_HEADER_HINT_CONTENT']) : '';
        if (!empty($hintContent)) {
            $defaultOpt[CURLOPT_HTTPHEADER][] = 'didi-header-hint-content: ' . $hintContent;
        }

        // for track_helper.php
        if (function_exists('trackGetContext')) {
            $trackContext = trackGetContext();
            if (!empty($trackContext)) {
                $defaultOpt[CURLOPT_HTTPHEADER][] = 'didi-enable-track-log: ' . $trackContext;
            }
        }
        if (isset($defaultOpt[CURLOPT_TIMEOUT_MS]) && isset($defaultOpt[CURLOPT_TIMEOUT])) {
            unset($defaultOpt[CURLOPT_TIMEOUT]);
            curl_setopt($curlInstance, CURLOPT_NOSIGNAL, 1);
        }
        if (isset($defaultOpt[CURLOPT_CONNECTTIMEOUT]) && isset($defaultOpt[CURLOPT_CONNECTTIMEOUT_MS])) {
            unset($defaultOpt[CURLOPT_CONNECTTIMEOUT]);
        }

        foreach ($defaultOpt as $k => $v) {
            curl_setopt($curlInstance, $k, $v);
        }

        $content = '';
        if ($defaultOpt[CURLOPT_POST] && !empty($params)) { //如果输入的是Post请求，并设置了请求参数，将post内容封装到CURLOPT_POSTFIELDS中
            if (is_array($params)) {
                if (!empty($defaultOpt[CURLOPT_HTTPHEADER])) {
                    $flag    = false;
                    $aHeader = $defaultOpt[CURLOPT_HTTPHEADER];
                    foreach ((array)$aHeader as $sHeaderSig) {
                        if (preg_replace('/\s+/', '', strtolower($sHeaderSig)) == 'content-type:application/json') { //去除中间的空格
                            $flag = true;
                            break;
                        }
                    }
                    if ($flag) {
                        $content = json_encode($params);
                    } else {
                        $content = http_build_query($params);
                    }
                } else {
                    $content = http_build_query($params);
                }
            } else {
                $content = $params;
            }
            curl_setopt($curlInstance, CURLOPT_POSTFIELDS, $content);
        }

        $jsonParams   = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $count        = $retries;
        $ret          = [];
        $errno        = 0;
        $errmsg       = '';
        $intTry       = 0;
        $sRetrySpanId = $spanId;
        while ($count--) {
            $intTry   = $retries - $count - 1;
            $ret      = curl_exec($curlInstance);
            $errno    = curl_errno($curlInstance);
            $errmsg   = curl_error($curlInstance);
            $httpCode = curl_getinfo($curlInstance, CURLINFO_HTTP_CODE);
            $info     = curl_getinfo($curlInstance);
            if ($httpCode == 200 && $ret) {
                break;
            } else {
                $aMsg = [
                    "cspanid" => $intTry > 0 ? $sRetrySpanId : $spanId,
                    "message" => "request fail",
                    "url" => $url,
                    "args" => $jsonParams,
                    "result" => $ret,
                    "retry" => $intTry,
                    "namelookup_time" => $info['namelookup_time'] ? $info['namelookup_time'] : "",
                    'connect_time' => $info["connect_time"] ? $info['namelookup_time'] : "",
                    'proc_time' => $info['total_time'] ? $info['total_time'] : "",
                ];

                /**
                 * 重试的日志中，新增字段 retry_spanid ，写入第一次的 span id
                 * 重试的时候，cspanid 生成新的span id
                 */
                if ($intTry > 0) {
                    $aMsg['retry_spanid'] = $spanId;
                }
                if (!isset($sSpanKey) && isset($defaultOpt[CURLOPT_HTTPHEADER][$sSpanKey]) && function_exists("gen_span_id")) {
                    $sRetrySpanId                              = gen_span_id();
                    $defaultOpt[CURLOPT_HTTPHEADER][$sSpanKey] = 'didi-header-spanid: ' . $sRetrySpanId;
                    curl_setopt($curlInstance, CURLOPT_HTTPHEADER, $defaultOpt[CURLOPT_HTTPHEADER]);
                }

                if (function_exists('com_log_warning')) {
                    com_log_warning('_com_http_failure', $errno, $errmsg, $aMsg);
                }

                // for track_helper.php
                if (function_exists('trackOnCurlFailure')) {
                    trackOnCurlFailure($url, $params, $defaultOpt, $retries - $count, $ret, $errno, $errmsg, curl_getinfo($curlInstance));
                }
            }
        }

        if (isset($GLOBALS['cspanId'])) {
            unset($GLOBALS['cspanId']);
        }

        $info = curl_getinfo($curlInstance);
        // for track_helper.php
        if (function_exists('trackOnCurlSuccess')) {
            trackOnCurlSuccess($url, $params, $defaultOpt, $retries - $count, $ret, $errno, $errmsg, $info);
        }

        //记录日志
        $key         = rand();
        $ci          = &get_instance();
        $isCliReq    = !empty($ci) && $ci->input->is_cli_request();
        $arrLogParam = [];
        if ($isCliReq) {
            $arrLogParam['url']    = $url;
            $arrLogParam['errno']  = $errno;
            $arrLogParam['errmsg'] = $errmsg;
        } else {
            $arrLogParam['url_' . $key]    = $url;
            $arrLogParam['errno_' . $key]  = $errno;
            $arrLogParam['errmsg_' . $key] = $errmsg;
        }

        $fields = ["url", "total_time", "namelookup_time", "connect_time", "pretransfer_time", "starttransfer_time", "redirect_time"];
        foreach ($fields as $v) {
            if ($isCliReq) {
                $arrLogParam[$v] = $info[$v];
            } else {
                $arrLogParam[$v . '_' . $key] = $info[$v];
            }
        }
        $arrLogParam['logId'] = get_logid();
        //记录日志 END

        if ($errno != 0) { //如果超时则打印出来
            if (function_exists('com_log_warning')) {
                com_log_warning("_com_http_failure", $errno, $errmsg, [
                    "args" => $jsonParams,
                    'proc_time' => $info['total_time'],
                    "namelookup_time" => $info['namelookup_time'],
                    'connect_time' => $info["connect_time"],
                ]);
            }
        } else {
            $bFilterLog = isset($aExtInfo['filter_log']) ? $aExtInfo['filter_log'] : false;
            if (!$bFilterLog) {
                if (function_exists('com_log_notice')) {
                    $time_elapsed_arr = [];
                    foreach ($fields as $v) {
                        $time_elapsed_arr[$v] = $info[$v];
                    }
                    $parsed_url = parse_url($info['url']);

                    $aMsg = [
                        "cspanid" => $intTry > 0 ? $sRetrySpanId : $spanId,
                        "url" => $url,
                        "args" => $jsonParams,
                        "response" => $ret,
                        "errno" => $errno,
                        "errmsg" => $errmsg,
                        "remote_host" => is_array($parsed_url) && isset($parsed_url['host']) ? $parsed_url['host'] : "",
                        "remote_port" => is_array($parsed_url) && isset($parsed_url['port']) ? $parsed_url['port'] : "",
                        "remote_path" => is_array($parsed_url) && isset($parsed_url['path']) ? $parsed_url['path'] : "",
                        "remote_query" => is_array($parsed_url) && isset($parsed_url['query']) ? $parsed_url['query'] : "",
                        "namelookup_time" => $info['namelookup_time'],
                        'connect_time' => $info["connect_time"],
                        'proc_time' => $info['total_time'],
                    ];
                    if ($intTry > 0) {
                        $aMsg['retry_spanid'] = $spanId;
                    }
                    com_log_notice('_com_http_success', array_merge($aMsg, $time_elapsed_arr));
                }
            }
        }
        curl_close($curlInstance);
        if ($httpCode != 200) {
            $errno = $httpCode;
        }
        $result = [
            'ret' => $ret,
            'errno' => $errno,
            'errmsg' => $errmsg,
        ];
    }

    return $result;
}

function curlNoLog($url, $params = [], $options = [], $retries = 2, $connectTime = 1)
{ // retries 重试次数, 默认为2
    $curlInstance = curl_init($url);
    $defaultOpt   = [
        CURLOPT_POST => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 1,
        CURLOPT_CONNECTTIMEOUT => intval($connectTime),
    ];
    if (is_array($options) && !empty($options)) {
        foreach ($options as $k => $v) {
            $defaultOpt[$k] = $v;
        }
    }
    $traceId = isset($_SERVER['HTTP_DIDI_HEADER_RID']) ? strval($_SERVER['HTTP_DIDI_HEADER_RID']) : '';
    if (!empty($traceId)) {
        $defaultOpt[CURLOPT_HTTPHEADER][] = 'didi-header-rid: ' . $traceId;
    }
    //add span id
    //$spanId = isset($_global_span_id) ? strval($_global_span_id) : '';
    if (function_exists("gen_span_id")) {
        $spanId = gen_span_id();
    } else {
        $spanId = isset($_global_span_id) ? strval($_global_span_id) : '';
    }
    if (!empty($spanId)) {
        $defaultOpt[CURLOPT_HTTPHEADER][] = 'didi-header-spanid: ' . $spanId;
    }

    //add hint code & hint content
    $hintCode = isset($_SERVER['HTTP_DIDI_HEADER_HINT_CODE']) ? strval($_SERVER['HTTP_DIDI_HEADER_HINT_CODE']) : '';
    if (!empty($hintCode)) {
        $defaultOpt[CURLOPT_HTTPHEADER][] = 'didi-header-hint-code: ' . $hintCode;
    }
    $hintContent = isset($_SERVER['HTTP_DIDI_HEADER_HINT_CONTENT']) ? strval($_SERVER['HTTP_DIDI_HEADER_HINT_CONTENT']) : '';
    if (!empty($hintContent)) {
        $defaultOpt[CURLOPT_HTTPHEADER][] = 'didi-header-hint-content: ' . $hintContent;
    }

    if (isset($defaultOpt[CURLOPT_TIMEOUT_MS]) && isset($defaultOpt[CURLOPT_TIMEOUT])) {
        unset($defaultOpt[CURLOPT_TIMEOUT]);
    }
    foreach ($defaultOpt as $k => $v) {
        curl_setopt($curlInstance, $k, $v);
    }

    $content = '';
    if ($defaultOpt[CURLOPT_POST] && !empty($params)) { //如果输入的是Post请求，并设置了请求参数，将post内容封装到CURLOPT_POSTFIELDS中
        if (is_array($params)) {
            $content = http_build_query($params);
        } else {
            $content = $params;
        }
        curl_setopt($curlInstance, CURLOPT_POSTFIELDS, $content);
    }

    $jsonParams = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $count      = $retries;
    $ret        = [];
    $errno      = 0;
    $errmsg     = '';
    while ($count--) {
        $ret    = curl_exec($curlInstance);
        $errno  = curl_errno($curlInstance);
        $errmsg = curl_error($curlInstance);
        if ($ret !== false) {
            break;
        } else {
            $intTry = $retries - $count;
            //log_warning('request fail|url:%s|content:%s|result:%s|errno:%s|errmsg:%s|try:%s[helper/curl_helper/curl]', $url, serialize($content), serialize($ret),$errno,$errmsg, $intTry);
            if (function_exists('com_log_warning')) {
                com_log_warning('_com_http_failure', $errno, $errmsg, ["cspanid" => $spanId, "message" => "request fail", "url" => $url, "args" => $jsonParams, "result" => $ret, "retry" => $retries]);
            }
        }
    }

    $fields = ["url", "total_time", "namelookup_time", "connect_time", "pretransfer_time", "starttransfer_time", "redirect_time"];
    $info   = curl_getinfo($curlInstance);

    //记录日志
    $key         = rand();
    $ci          = &get_instance();
    $isCliReq    = !empty($ci) && $ci->input->is_cli_request();
    $arrLogParam = [];
    if ($isCliReq) {
        $arrLogParam['url']    = $url;
        $arrLogParam['errno']  = $errno;
        $arrLogParam['errmsg'] = $errmsg;
    } else {
        $arrLogParam['url_' . $key]    = $url;
        $arrLogParam['errno_' . $key]  = $errno;
        $arrLogParam['errmsg_' . $key] = $errmsg;
    }

    foreach ($fields as $v) {
        if ($isCliReq) {
            $arrLogParam[$v] = $info[$v];
        } else {
            $arrLogParam[$v . '_' . $key] = $info[$v];
        }
    }

    $arrLogParam['logId'] = get_logid();
    //记录日志 END

    if ($errno != 0) { //如果超时则打印出来
        //log_fatal('error','call '.__FUNCTION__." curl [url=$url] [errno=$errno] [errmsg=$errmsg] with params ".print_r($params,true));
        if (function_exists('com_log_warning')) {
            com_log_warning("_com_http_failure", $errno, $errmsg, ["cspanid" => $spanId, "args" => $jsonParams]);
        }
    } else {
        if (function_exists('com_log_notice')) {
            $time_elapsed_arr = [];
            foreach ($fields as $v) {
                $time_elapsed_arr[$v] = $info[$v];
            }
            com_log_notice('_com_http_success', array_merge(["cspanid" => $spanId, "url" => $url, "args" => $jsonParams, "response" => $ret, "errno" => $errno, "errmsg" => $errmsg, 'proc_time' => $info['total_time']], $time_elapsed_arr));
        }
    }
    curl_close($curlInstance);

//    return $ret;

    $result = [
        'ret' => $ret,
        'errno' => $errno,
        'errmsg' => $errmsg,
    ];
    return $result;

}

/**
 * multi_curl
 *
 * @param array $urls
 * @param array $params
 * @param array $options
 * @param int   $retries
 * @param int   $connectTime
 *
 * @return array
 * @author AndyCong<congming@diditaxi.com.cn>
 * @date   2015-03-06
 */
function curlMultiProcess($urls = [], $params = [], $options = [], $retries = 2, $connectTime = 1)
{
    $mh         = curl_multi_init();
    $arr_spanid = [];
    $defaultOpt = [
        CURLOPT_POST => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 1,
        CURLOPT_CONNECTTIMEOUT => intval($connectTime),
    ];

    if (is_array($options) && !empty($options)) {
        foreach ($options as $k => $v) {
            $defaultOpt[$k] = $v;
        }
    }

    if (!isset($defaultOpt[CURLOPT_HTTPHEADER])) {
        $defaultOpt[CURLOPT_HTTPHEADER] = [];
    }
    $traceId = isset($_SERVER['HTTP_DIDI_HEADER_RID']) ? strval($_SERVER['HTTP_DIDI_HEADER_RID']) : '';
    if (!empty($traceId)) {
        $defaultOpt[CURLOPT_HTTPHEADER][] = 'didi-header-rid: ' . $traceId;
    }

    //add hint code & hint content
    $hintCode = isset($_SERVER['HTTP_DIDI_HEADER_HINT_CODE']) ? strval($_SERVER['HTTP_DIDI_HEADER_HINT_CODE']) : '';
    if (!empty($hintCode)) {
        $defaultOpt[CURLOPT_HTTPHEADER][] = 'didi-header-hint-code: ' . $hintCode;
    }
    $hintContent = isset($_SERVER['HTTP_DIDI_HEADER_HINT_CONTENT']) ? strval($_SERVER['HTTP_DIDI_HEADER_HINT_CONTENT']) : '';
    if (!empty($hintContent)) {
        $defaultOpt[CURLOPT_HTTPHEADER][] = 'didi-header-hint-content: ' . $hintContent;
    }

    $conn = [];
    foreach ($urls as $i => $url) {
        //add span id
        //$spanId = isset($_global_span_id) ? st4rval($_global_span_id) : '';
        if (!empty($GLOBALS['cspanId'])) {
            $spanId = $GLOBALS['cspanId'];
        } else {
            if (function_exists("gen_span_id")) {
                $spanId = gen_span_id();
            } else {
                $spanId = isset($_global_span_id) ? strval($_global_span_id) : '';
            }
        }

        if (isset($defaultOpt[CURLOPT_HTTPHEADER]['spanid'])) {
            unset($defaultOpt[CURLOPT_HTTPHEADER]['spanid']);
        }

        if (!empty($spanId)) {
            $defaultOpt[CURLOPT_HTTPHEADER]['spanid'] = 'didi-header-spanid: ' . $spanId;
            $arr_spanid[$i]                           = $spanId;
        }

        $conn[$i] = curl_init($url);
        if (isset($defaultOpt[CURLOPT_TIMEOUT_MS]) && isset($defaultOpt[CURLOPT_TIMEOUT])) {
            unset($defaultOpt[CURLOPT_TIMEOUT]);
        }
        foreach ($defaultOpt as $k => $v) {
            curl_setopt($conn[$i], $k, $v);
        }
        if ($defaultOpt[CURLOPT_POST] && !empty($params)) { //如果输入的是Post请求，并设置了请求参数，将post内容封装到CURLOPT_POSTFIELDS中
            if (isset($params[$i]) && is_array($params[$i])) {
                $content = http_build_query($params[$i]);
            } else {
                $content = $params;
            }
            curl_setopt($conn[$i], CURLOPT_POSTFIELDS, $content);
        }
        curl_multi_add_handle($mh, $conn[$i]);
    }

    $active = 0;
    do {
        $ret = curl_multi_exec($mh, $active);
    } while ($ret == CURLM_CALL_MULTI_PERFORM);

    while ($active and $ret == CURLM_OK) {
        if (curl_multi_select($mh) != -1) {
            do {
                $ret = curl_multi_exec($mh, $active);
            } while ($ret == CURLM_CALL_MULTI_PERFORM);
        }
    }

    $errnos    = [];
    $errmsgs   = [];
    $arrResult = [];
    foreach ($conn as $key => $handle) {
        $curl_info       = curl_getinfo($handle);
        $errnos[$key]    = curl_errno($handle);
        $errmsgs[$key]   = curl_error($handle);
        $arrResult[$key] = curl_multi_getcontent($handle);
        curl_multi_remove_handle($mh, $handle);
        if (function_exists("com_log_notice")) {
            com_log_notice('_com_http_success', [
                "cspanid" => $arr_spanid[$key],
                "url" => $urls[$key],
                "args" => $params[$key],
                "response" => $arrResult[$key],
                "errno" => $errnos[$key],
                "errmsg" => $errmsgs[$key],
                'proc_time' => $curl_info['total_time'],
                "namelookup_time" => $curl_info['namelookup_time'],
                "connect_time" => $curl_info['connect_time'],
                "pretransfer_time" => $curl_info['pretransfer_time'],
                "starttransfer_time" => $curl_info['starttransfer_time'],
                "redirect_time" => $curl_info['redirect_time'],
            ]);
        }
    }
    curl_multi_close($mh);
    $arrLogParam          = [
        'mcurl_urls' => json_encode($urls),
        'mcurl_errnos' => json_encode($errnos),
        'mcurl_errmsg' => json_encode($errmsgs),
    ];
    $arrLogParam['logId'] = get_logid();
    log_add_basic($arrLogParam);

    $result = [
        'ret' => $ret,
        'result' => $arrResult,
        'errno' => $errnos,
        'errmsg' => $errmsgs,
    ];

    return $result;
}
