<?php
/***************************************************************************
 *
 * Copyright (c) 2014 xiaojukeji.com, Inc. All Rights Reserved
 * $Id$
 *
 **************************************************************************/



/**
 * @file log_helper.php
 * @author litianyi(litianyi@diditaxi.com.cn)
 * @date 2014/04/23 19:51:29
 * @version $Revision$
 * @brief
 *
 * 增加了一些全局变量的内容  $_global_log_id 用于记录整个请求期间的内容
 * 定义了一部分新的log函数  log_warning log_fatal log_trace 等
 * 增加了一个log_request日志，主要用于记录请求的REQUEST内容, 同时注册一个log_finish方法到regeister_shundown_function中，使得请求结束时打印 notice日志出来。 （日志级别需要在notice级别上）
 *
 *
 **/

//$_global_log_id=0;
if ( ! function_exists('t_hit_strace_sampling') )
{
    function t_hit_strace_sampling() {
        global $_g_should_enable_strace;

        if (isset($_g_should_enable_strace) && !empty($_g_should_enable_strace)) {
            return $_g_should_enable_strace;
        }

        // 如果从上游带过来的traceid是33位的,而且第33位的值是1,那么则表示无论如何也要让这个请求命中strace日志采样
        $trace_id = get_traceid();
        if ($trace_id != '' && strlen($trace_id) == 33) {
            $trace_id_sample_bit = substr($trace_id, 32, 1);
            if ($trace_id_sample_bit == '1') {
                $_g_should_enable_strace = true;
            } else {
                $_g_should_enable_strace = false;
            }
            return $_g_should_enable_strace;
        }

        if (!isset($_g_strace_sample_rate) or empty($_g_strace_sample_rate)) {
            $_g_strace_sample_rate = 10000;
        }
        if (!isset($_g_should_enable_strace) or empty($_g_should_enable_strace)) {
            $rnd = mt_rand() % $_g_strace_sample_rate;
            if ($rnd == 0) {
                $_g_should_enable_strace = true;
                // 给32位trace_id增加1位作为下游强制采样的标示
                if ($trace_id != '' && strlen($trace_id) == 32 ) {
                    set_traceid($trace_id.'1');
                }
            } else {
                $_g_should_enable_strace = false;
            }
        }
        return $_g_should_enable_strace;
    }
}


if( ! function_exists('gen_logid'))
{
    function gen_logid(){
        global $_global_log_id;
        if($_global_log_id != 0){
            return $_global_log_id;
        }
        if (isset($_SERVER['HTTP_CLIENTAPPID'])) {
            //client 传入了id则直接使用
            //转换成数字，否则后端使用可能有问题
            $_global_log_id = intval($_SERVER['HTTP_CLIENTAPPID']);
            $_global_log_id *=100; //末尾两位用于累计对后端的调用过程
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
        $_global_log_id *=100;
    }

}

if( ! function_exists('gen_traceid'))
{
    function gen_traceid(){
        global $_global_trace_id;
        if($_global_trace_id != 0){
            return $_global_trace_id;
        }
        if (isset($_SERVER['HTTP_DIDI_HEADER_RID'])) {
            $_global_trace_id = $_SERVER['HTTP_DIDI_HEADER_RID'];
            return $_global_trace_id;
        }
    }

}
/**
 * get parent span id
 */
if( ! function_exists('t_get_parent_span_id'))
{
    function t_get_parent_span_id(){
        global $_global_parent_span_id;
        if($_global_parent_span_id!= ''){
            return $_global_parent_span_id ;
        }
        if (isset($_SERVER['HTTP_DIDI_HEADER_SPANID'])) {
            $_global_parent_span_id = $_SERVER['HTTP_DIDI_HEADER_SPANID'];
            return $_global_parent_span_id;
        }
        $_global_parent_span_id = sprintf('%016s',0);
        return $_global_parent_span_id;
    }
}

/**
 * get span id
 */
if( ! function_exists('t_get_span_id'))
{
    function t_get_span_id(){
        global $_global_span_id;
        if($_global_span_id!= ''){
            return $_global_span_id ;
        }
        $_global_span_id =  t_gen_random_id();
        return $_global_span_id;
    }
}

/**
 * gen random 64bit id with ip,timestamp,rand int
 */
if( ! function_exists('t_gen_random_id'))
{
    function t_gen_random_id(){
        $reqip = '127.0.0.1';
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $reqip = $_SERVER['REMOTE_ADDR'];
        } elseif(isset($_SERVER['SERVER_ADDR'])){
            $reqip = $_SERVER['SERVER_ADDR'];
        }
        $time = gettimeofday();
        $time = $time['sec'] + $time['usec'];
        $rand = mt_rand();
        $ip = ip2long($reqip);
        $random_id = t_id_to_hex($ip ^ $time) ."".t_id_to_hex($rand);
        return $random_id;
    }
}

/**
 * int to hex string
 */
if( ! function_exists('t_id_to_hex')){
    function t_id_to_hex($id){
        return sprintf('%08s',dechex($id));
    }
}

/**
 * Add some fields to the __log__ arguments list.
 *
 *
 * @param $errno: (optional)
 *
 * @param $errmsg: (optional) If it exists, $errno must be given)
 *
 */
if( ! function_exists('t_unshift_log_message')) {
    function t_unshift_log_message() {
        $num_args = func_num_args();
        $arr = func_get_arg(0);
        $dltag = func_get_arg(1);
        $format = array_shift($arr);
        $prefix = '';
        if ($num_args == 2) {
            // 处理参数中只有$DLTAG的情况
            $prefix .= '||';
        } elseif ($num_args == 3){
            $errno = func_get_arg(2);
            $prefix .= 'errno=' .$errno ."||";
        } elseif ($num_args == 4) {
            // 处理参数中有$DLTAG, $errno, $errmsg的情况
            $errno = func_get_arg(2);
            $errmsg = func_get_arg(3);
            $prefix .= 'errno=' .$errno ."||errmsg=" .$errmsg ."||";
        } else {
            // TODO 参数个数不匹配暂时就先不处理了
        }
        $new_format = $prefix.$format;
        array_unshift($arr, $new_format);
        return $arr;
    }
}

if (! function_exists('t_push_formatted_log_message')) {
    /**
     * 用于将数组格式的参数列表转换为字符串格式的可变参数列表
     *
     * @return array 字符串格式参数列表数组
     */
    function t_push_formatted_log_message() {
        $arr = func_get_arg(0);
        $ret = array();
        $message = '';
        if ( is_array($arr) ) {
            foreach ($arr as $key => $value) {
                $tmp[] = "$key=$value";
            }
        }
        $message = implode('||', $tmp);
        array_unshift($ret, $message);
        return $ret;
    }
}

/**
 * get global logid
 */
if( ! function_exists('get_logid'))
{
    function get_logid(){
        global $_global_log_id;
        return $_global_log_id;
    }
}

/**
 * get global traceid
 */
if( ! function_exists('get_traceid'))
{
    function get_traceid(){
        global $_global_trace_id;
        return $_global_trace_id;
    }
}

/**
 * set global traceid
 */
if (! function_exists('set_traceid'))
{
    function set_traceid($trace_id) {
        global $_global_trace_id;
        $_global_trace_id = $trace_id;
    }
}

/**
 * increment global logid, default step length 1;
 */
if( ! function_exists('inc_logid'))
{
    function inc_logid($step=1){
        global $_global_log_id;
        $_global_log_id+=$step;
        return $_global_log_id;
    }

}

if( !function_exists('log_fatal'))
{
    function log_fatal()
    {
        static $_log;
        $_log =& load_class('Log');

        $args = func_get_args();
        $format = array_shift($args);
        array_unshift($args, '_msg='.$format);
        $_log->fatal($args);
    }
}


if ( !function_exists('parse_biz_id') )
{
    function parse_biz_id($args) {
        $ctx = array();
        $mapper = array(
            "order_id"        => "order_id",
            "orderid"         => "order_id",
            "iorderid"        => "order_id",
            "sorderid"        => "s_order_id",
            "oid"             => "order_id",

            "passenger_id"    => "passenger_id",
            "passengerid"     => "passenger_id",
            "pid"             => "passenger_id",

            "passenger_phone" => "passenger_phone",
            "passengerphone"  => "passenger_phone",

            "driver_id"       => "driver_id",
            "driverid"        => "driver_id",
            "did"             => "driver_id",

            "driver_phone"    => "driver_phone",
            "driverphone"     => "driver_phone",
        );
        $array_walk_callback = function (&$value, $key) use (&$ctx, $mapper) {
            $formatted_key = strtolower($key);
            if ( array_key_exists($formatted_key, $mapper) ) {
                $target_key = $mapper[$formatted_key];
                if ( $target_key == "order_id" && !is_numeric($value) ) {
                    $ctx["s_order_id"] = $value;
                }
                $ctx[$target_key] = $value;
            }
        };
        array_walk_recursive($args, $array_walk_callback);
        return $ctx;
    }

}


if( !function_exists('com_log_fatal'))
{
    function com_log_fatal()
    {
        static $_log;
        $_log =& load_class('Log');

        $num_args = func_num_args();
        $args = func_get_args();
        if ($num_args >= 4) {
            // TODO assert
            $dltag = array_shift($args);
            $errno = array_shift($args);
            $errmsg = array_shift($args);
            $_format = $args[0];
            if (is_array($_format)) {
                $args = t_push_formatted_log_message($_format);
            }
            $args = t_unshift_log_message($args, $dltag, $errno, $errmsg);
            $_log->fatal($args, $dltag);
        }
    }
}

if( ! function_exists('log_warning'))
{
    function log_warning()
    {
        static $_log;
        $_log =& load_class('Log');

        $args = func_get_args();
        $format = array_shift($args);
        array_unshift($args, '_msg='.$format);
        $_log->warning($args);
    }
}

if( ! function_exists('com_log_warning'))
{
    function com_log_warning()
    {
        static $_log;
        $_log =& load_class('Log');

        $num_args = func_num_args();
        $args = func_get_args();
        if ($num_args >= 4) {
            // TODO assert
            $dltag = array_shift($args);
            $errno = array_shift($args);
            $errmsg = array_shift($args);
            $_format = $args[0];
            if (is_array($_format)) {
                $args = t_push_formatted_log_message($_format);
            }
            $args = t_unshift_log_message($args, $dltag, $errno, $errmsg);
            $_log->warning($args, $dltag);
        }
    }
}

if( ! function_exists('com_log_strace'))
{
    function com_log_strace()
    {
        static $_log;
        $_log =& load_class('Log');

        $args = func_get_args();
        $num_args = func_num_args();
        $dltag = '_undef';

        if ( !t_hit_strace_sampling() ) {
            return;
        }
        if ($num_args >= 2) {
            // TODO assert
            $dltag = array_shift($args);
            $_format = $args[0];
            // convert array format args as string
            if (is_array($_format)) {
                $args = t_push_formatted_log_message($_format);
            }
            $_log->strace($args, $dltag);
        }

    }
}

if( ! function_exists('log_notice'))
{
    function log_notice()
    {
        static $_log;
        $_log =& load_class('Log');

        $args = func_get_args();
        $format = array_shift($args);
        array_unshift($args, '_msg='.$format);
        $_log->notice($args);
    }
}

if( ! function_exists('com_log_notice')) {
    // TODO fill logic
    function com_log_notice()
    {
        static $_log;
        $_log =& load_class('Log');

        $args = func_get_args();
        $num_args = func_num_args();
        $dltag = '_undef';
        if ($num_args >= 2) {
            // TODO assert
            $dltag = array_shift($args);
            $_format = $args[0];
            // convert array format args as string
            if (is_array($_format)) {
                $args = t_push_formatted_log_message($_format);
            }
            $_log->notice($args, $dltag);
        }
    }
}

if( ! function_exists('log_trace'))
{
    function log_trace()
    {
        static $_log;
        $_log =& load_class('Log');

        $args = func_get_args();
        $format = array_shift($args);
        array_unshift($args, '_msg='.$format);
        $_log->trace($args);
    }
}

if( ! function_exists('log_debug'))
{
    function log_debug()
    {
        static $_log;
        $_log =& load_class('Log');

        $args = func_get_args();
        $format = array_shift($args);
        array_unshift($args, '_msg='.$format);
        $_log->debug($args);
    }
}

//增加日志basic数组
if( ! function_exists('log_add_basic'))
{
    function log_add_basic($basic_info)
    {
        static $_log;
        $_log =& load_class('Log');
        $_log->add_basic($basic_info);
    }
}


if( ! function_exists('log_request'))
{
    function log_request(){
        ob_start();
        $logid=get_logid();
        $traceid=get_traceid();
        //add span id and parent span id
        $pspanid=t_get_parent_span_id();
        $spanid=t_get_span_id();
        $is_cli = defined('STDIN');
        $cli_params = array();
        if ($is_cli) {
            global $argv;
            $cli_params = $argv;
            $url = "script:".$cli_params[0];
            array_shift($cli_params);
        } else {
            $url = $_SERVER['REQUEST_URI'];
        }
        $post_arr = array();
        foreach($_POST as $key=>$value)
        {
            if(0==strncmp($key,"__x_",4)) continue;
            $post_arr[$key] = $value;
        }
        if($is_cli) {
            $out = array('argv'=>$cli_params);
        }
        else {
            $out= array(
                'url' => $url,
                'from' => $_SERVER['REMOTE_ADDR'],
                'args' => json_encode($post_arr),
            );
        }
        $arr = explode('?',$url);
        log_add_basic(array(
                'traceid' => $traceid,
                'pspanid' => $pspanid,
                'spanid' => $spanid,
                'logid'=>$logid,
                'uri'=>$arr[0],
            )
        );
        //log_notice('input_param : '.serialize($out));
        com_log_notice('_com_request_in', $out);

        //暂时放在这里，后续可能会设置公共的shutdown函数
        register_shutdown_function('log_finish');
    }

}

//在请求完成时flush日志到文件中
if( ! function_exists('log_finish'))
{
    function log_finish()
    {
        static $_log;
        $_log =& load_class('Log');

        global $BM,$class,$method;

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

        /*$logid=get_logid();
          log_add_basic(array(
          'logid'=>$logid,
          )
          );*/

        $response = ob_get_flush();

        //log_notice("time: total=%f load_base=%f ac_exe=%f cache=%f (s)][memory: use=%f peak=%f (MB)"
        com_log_notice("_com_request_out", "response=%s||proc_time=%f||time=[total=%f load_base=%f ac_exe=%f (s)]||memory=[use=%f peak=%f (MB)]"
            ,$response
            ,$total_execution_time
            ,$total_execution_time
            ,$loading_time
            ,$controller_execution_time
            //,isset($GLOBALS['__globals_redis_time_consuming__'])?$GLOBALS['__globals_redis_time_consuming__']:0
            ,memory_get_usage(true)/1024.0/1024.0
            ,memory_get_peak_usage(true)/1024.0/1024.0
        );
        $_log->flush();
    }
}

if( ! function_exists('log_params_comb'))
{
    function log_params_comb($data)
    {
        foreach ($data as $key => $val) {
            $tmp[] = "$key=$val";
        }
        return $keyStr = implode( "|" , $tmp);
    }
}

if ( ! function_exists( 'logFormat' ) ) {
    function logFormat( $func, $errno, $msg, $input = '', $output = '' )
    {
        $_log =& load_class( 'Log' );

        if ( is_array( $input ) ) {
            $input = json_encode( $input );
        }
        if ( is_array( $output ) ) {
            $output = json_encode( $output );
        }
        $log_msg = sprintf( "errno=%s||errmsg=%s||INPUT:[%s]||OUTPUT=[%s]", $errno, $msg, $input,
            $output );
        $_log->$func( array(0=>$log_msg) );
    }
}


if( ! function_exists('log_play'))
{
    function log_play()
    {
        static $_log;
        $_log =& load_class('PlayLog');

        $args = func_get_args();
        $_log->log_play($args[0],$args);
    }
}

if( ! function_exists('log_play_queue'))
{
    function log_play_queue()
    {
        static $_log;
        $_log =& load_class('PlayLog');

        $args = func_get_args();
        $_log->log_play_queue($args);
    }
}

if( ! function_exists('log_play_sql'))
{
    function log_play_sql()
    {
        static $_log;
        $_log =& load_class('PlayLog');

        $args = func_get_args();
        $_log->log_play_sql($args);
    }
}

//sgen_logid(); //生成logid

//END of LOG_HELPER

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
