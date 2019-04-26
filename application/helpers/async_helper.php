<?php

use Disf\SPL\Carrera;
use Disf\SPL\Discovery\External;
use Disf\SPL\Disf\Config;
use Disf\SPL\Introspection\Introspection;
use Disf\SPL\Log;

/********************************************
 * # desc:    异步回调处理方法
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-07-11
 ********************************************/

if (!function_exists('asyncCallFunc')) {
    /**
     * @param $class            string  类名
     * @param $function         string  方法名
     * @param array $params array   参数数组, 元素个数代表参数个数
     *
     * 比如:
     * asynctask/Callrecord_model->createApiCallRecord(array("city_id"=>1))
     *
     * 参数:
     * $class       ='asynctask/Callrecord_model'
     * $function    ='createApiCallRecord'
     * $params      =[array("city_id"=>1)]
     *
     * @return array 格式: Array ( [code] => 0 [msg] => OK [key] => 06876ab139c19bfd456d8b6120371259 )
     */
    function asyncCallFunc($class, $function, $params = [])
    {
        $msg = [
            'class' => $class,
            'function' => $function,
            'params' => json_encode($params, true), //为了方便加密
            'current_time' => time(),
        ];
        $ci =& get_instance();
        $ci->load->config('async_call');
        $salt_token = $ci->config->item('async_salt_token');
        $msg['sig'] = getSignature($msg, $salt_token);

        if(ENVIRONMENT=="production"){
            $topic = "itstool_async";
        }else{
            $topic = "itstool_async_test";
        }
        $mqdata = [
            "topic"=>$topic,
            "key"=>get_traceid(),
            "body"=>$msg,
        ];

        //发送实时消息
        //print_r($mqdata);
        com_log_notice('_asyncCallFunc_msg',['mqdata'=>json_encode($mqdata),]);
        $retJson = httpPOST("http://10.88.128.149:30796/produce/v1/sendMessage",$mqdata,0,"json");
        $result = json_decode($retJson,true);
        com_log_notice('_asyncCallFunc_result',['result'=>$result,]);
        if(!isset($result["code"]) || $result["code"]!=0){
            throw new \Exception("produce_sendMessage_error");
        }
        return $result;
    }
}

if (!function_exists('checkSign')) {
    /**
     * 验证签名
     * @param $params
     * @param $secret
     * @return bool
     */
    function checkSign($params, $secret)
    {
        $sign = isset($params['sig']) ? $params['sig'] : "";
        if (isset($params['sig'])) {
            unset($params['sig']);
        }

        //拦截ddmp附加信息
        foreach ($params as $pKey=>$pItem){
            if(strpos($pKey,"carrera")===0){
                unset($params[$pKey]);
            }
        }
        $verifySign = getSignature($params, $secret);
        if ($verifySign != $sign) {
            return false;
        }
        return true;
    }
}

if (!function_exists('getSignature')) {
    /**
     * @param array $params API调用的请求参数集合的关联数组，不包含sign参数
     * @param string $secret 申请到的秘钥 123456
     * @return string
     */
    function getSignature($params, $secret)
    {
        $str = '';  //待签名字符串
        //先将参数以其参数名的字典序升序进行排序
        ksort($params);
        //遍历排序后的参数数组中的每一个key/value对
        foreach ($params as $k => $v) {
            //为key/value对生成一个key=value格式的字符串，并拼接到待签名字符串后面
            $str .= "$k=" . urldecode($v);
        }
        //将签名密钥拼接到签名字符串最后面
        $str .= $secret;
        //通过md5算法为签名字符串生成一个md5签名，该签名就是我们要追加的sign参数值
        return md5($str);
    }
}

if (!function_exists('isOpenApiV5')) {
    /**
     * 是否为v5版本openApi
     *
     * @param string uninx时间戳
     * @return bool
     */
    function isOpenApiV5($date)
    {
        return $date>=strtotime('2018-06-01');
    }
}

if (!function_exists('des_encrypt')) {
    /** des加密 **/
    function des_encrypt($str, $key)
    {
        $block = @mcrypt_get_block_size('des', 'ecb');
        $pad = $block - (strlen($str) % $block);
        $str .= str_repeat(chr($pad), $pad);
        return safe_b64encode(@mcrypt_encrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB));
    }
}

if (!function_exists('des_decrypt')) {
    /** des解密 **/
    function des_decrypt($str, $key)
    {
        $str = safe_b64decode($str);
        $str = @mcrypt_decrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);
        $len = strlen($str);
        $pad = ord($str[$len - 1]);
        return substr($str, 0, $len - $pad);
    }
}


if (!function_exists('safe_b64encode')) {
    //处理特殊字符
    function safe_b64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
        return $data;
    }
}

if (!function_exists('safe_b64decode')) {
    //解析特殊字符
    function safe_b64decode($string)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
}

if (!function_exists('isInt')) {
    /**
     * 验证字符串是否为数字
     * @param $number string
     * @return bool
     */
    function isInt($number)
    {
        if (is_string($number) && preg_match("/^\d*$/", $number)) {
            return true;
        }
        return false;
    }
}
