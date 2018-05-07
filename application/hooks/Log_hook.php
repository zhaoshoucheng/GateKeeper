<?php

//本global_log_id 用于将access.log中的logid
//和用户日志中的logid对应起来，
//这样，用户便知道用户日志是源自哪一个web请求
$_global_log_id = 0;
$out_content = '';

function init_logid()
{
    global $_global_log_id;
    ob_start('do_ob_callback');
    if (isset($_SERVER['HTTP_CLIENTAPPID'])) {
        //client 传入了id则直接使用
        //转换成数字，否则后端使用可能有问题
        $_global_log_id = intval($_SERVER['HTTP_CLIENTAPPID']);
        return;
    }   
    //通过ip和当前时间算一个id
    $reqip = '127.0.0.1';
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $reqip = $_SERVER['REMOTE_ADDR'];
    } elseif(isset($_SERVER['SERVER_ADDR'])){
        $reqip = $_SERVER['SERVER_ADDR'];
    }
    $time = gettimeofday(); 
    $time = $time['sec'] * 100 + $time['usec'];
    $ip = ip2long($reqip);
    $_global_log_id = ($time ^ $ip)  & 0xFFFFFFFF;
}

function do_ob_callback($ob)
{
    global $out_content;
    $out_content .= $ob;
    return $ob;
}

function get_logid()
{
    global $_global_log_id;
    return $_global_log_id;
}


/*
function log_request()
{
    $post_arr = array();
    foreach($_POST as $key=>$value)
    {
        if(0==strncmp($key,"__x_",4)) continue;
        $post_arr[$key] = $value;
    }
    $reqip = '127.0.0.1';
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $reqip = $_SERVER['REMOTE_ADDR'];
    } elseif(isset($_SERVER['SERVER_ADDR'])){
        $reqip = $_SERVER['SERVER_ADDR'];
    }
        $out= array(
        'remote' => $reqip,
        'post' => $post_arr,
        );
    $out = json_encode($out);
    //log_message('notice', $out);
    //暂时放在这里，后续可能会设置公共的shutdown函数
    register_shutdown_function('log_finish', $out);
}

//在请求完成时flush日志到文件中
function log_finish($input)
{
    
    global $BM,$class,$method;
    global $out_content;

    $total_execution_time = $BM->elapsed_time(
            'total_execution_time_start'
            );

    $loading_time = $BM->elapsed_time(
            'loading_time:_base_classes_start',
            'loading_time:_base_classes_end'
            );

    $controller_execution_time = $BM->elapsed_time(
            'controller_execution_time_( '.$class.' / '.$method.' )_start',
            'controller_execution_time_( '.$class.' / '.$method.' )_end'
            );

    $CI = &get_instance();
    if(empty($CI) || empty($CI->uri)){
        return;
    }
    $whiteUriPrefix = array('road', 'flow', 'imggroup', 'junction', 'timingtask', 'timingversion', 'tidemgr');
    $reqUri = trim($CI->uri->ruri_string(), '/');
    $reqUriArr = explode('/', $reqUri);
    if(empty($reqUriArr) || !in_array(strtolower($reqUriArr[0]), $whiteUriPrefix)){
        return;
    }

    if(empty($out_content)) {
		$out_content = ob_get_contents();
    }
    if($_SERVER['REQUEST_METHOD'] != 'POST'){
        //GET只返回json数据的errno和errmsg
        $arr = json_decode($out_content, true);
        if(!is_array($arr)) {
            return;
        }
    }
    
    log_message('notice',sprintf("[in=%s][out=%s][time: total=%f load_base=%f ac_exe=%f (s)][memory: use=%f peak=%f (MB)"
            ,$input
            ,trim($out_content)
            ,$total_execution_time
            ,$loading_time
            ,$controller_execution_time
            ,memory_get_usage(true)/1024.0/1024.0
            ,memory_get_peak_usage(true)/1024.0/1024.0
            )
    );
}
*/