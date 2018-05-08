<?php
if( !function_exists('pre_controller')){
    function pre_controller(){
        $_global_log_id = 0;
        $ci = &get_instance();
        $ci->load->helper('log');

        global $_g_should_enable_strace;
        $_g_should_enable_strace = true;
        gen_logid();
        gen_traceid();
        log_request();
    }
}