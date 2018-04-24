<?php

if (!function_exists('sendMail')) {
    function sendMail($to, $subject, $content) {
        $mail_config = array();
        $CI =& get_instance();
        if ($CI->config->load('mail', TRUE)) {
            $configs = $CI->config->item('mail', 'mail');

            foreach ($configs as $name => $conf) {
                $mail_config[$name] = $conf;
            }

        }

        $CI->load->library('email');
        $CI->email->initialize($mail_config);
        $CI->email->from('sts_traffic@didichuxing.com', '智慧交通信号灯项目');
        $CI->email->to($to);
        $subject = '评估报表';
        $CI->email->subject($subject);
        $CI->email->message($content);
        return $CI->email->send();
    }
}
