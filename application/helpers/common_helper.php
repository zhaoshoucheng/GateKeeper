<?php

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

if (!function_exists('getSign')) {
    /**
     * @param map $params API调用的请求参数集合的关联数组，不包含sign参数
     * @param string $secret 申请到的app_id对应秘钥
     * @return string
     */
    function getSign($params, $secret)
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
        $str = $str . "&" . $secret;
        echo $str;
        echo "<br/>";
        //通过md5算法为签名字符串生成一个md5签名, 从第7位开始取16位
        return substr(md5($str), 7, 16);
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

if(!function_exists('dateRange')) {
    /**
     * 获取指定时间范围内的粒度为 天 的倍数的 指定格式 的数据集合
     *
     * @param $start
     * @param $end
     * @param int $skip
     * @param string $format
     * @return array
     */
    function dateRange($start, $end, $skip = 1, $format = 'Y-m-d')
    {
        return array_map(function ($item) use ($format) {
            return date($format, $item);
        }, range(strtotime($start), strtotime($end), $skip * 60 * 60 * 24));
    }
}

if(!function_exists('hourRange')) {
    /**
     * 获取指定时间范围内的 粒度为 分钟 的 指定格式的数据集合
     *
     * @param string $start
     * @param string $end
     * @param int $skip
     * @param string $format
     * @return array
     */
    function hourRange($start = '00:00', $end = '23:00', $skip = 30, $format = 'H:i')
    {
        return array_map(function ($item) use ($format) {
            return date($format, $item);
        }, range(strtotime($start), strtotime($end), $skip * 60));
    }
}

if(!function_exists('arrayMergeRecursive')) {
    /**
     * 递归合并数组（支持数字键数组）
     * @param $target
     * @param $source
     * @return mixed
     */
    function arrayMergeRecursive($target, $source)
    {
        $tKeys = array_keys($target);
        $sKeys = array_keys($source);

        $keys = array_unique(array_merge($tKeys, $sKeys));

        foreach ($keys as $key) {
            if(array_key_exists($key, $source) && !array_key_exists($key, $target)) {
                $target[$key] = $source[$key];
            } elseif (array_key_exists($key, $source) && array_key_exists($key, $target)) {
                if(is_array($target[$key]) && is_array($source[$key])) {
                    $target[$key] = arrayMergeRecursive($target[$key], $source[$key]);
                } else {
                    $target[$key] = [$target[$key], $source[$key]];
                }
            }
        }
        return $target;
    }
}
