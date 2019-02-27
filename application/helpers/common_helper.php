<?php

if (!function_exists('checkSign')) {
    /**
     * 验证签名
     *
     * @param $params
     * @param $secret
     *
     * @return bool
     */
    function checkSign($params, $secret)
    {
        $sign = isset($params['sig']) ? $params['sig'] : "";
        if (isset($params['sig'])) {
            unset($params['sig']);
        }

        //拦截ddmp附加信息
        foreach ($params as $pKey => $pItem) {
            if (strpos($pKey, "carrera") === 0) {
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
     * @param array  $params API调用的请求参数集合的关联数组，不包含sign参数
     * @param string $secret 申请到的秘钥 123456
     *
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
     * @param array  $params API调用的请求参数集合的关联数组，不包含sign参数
     * @param string $secret 申请到的app_id对应秘钥
     *
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
            $str .= "$k=" . urldecode($v) . "&";
        }

        //将签名密钥拼接到签名字符串最后面
        $str = $str . $secret;
        //echo $str;
        //echo "<br/>";
        //通过md5算法为签名字符串生成一个md5签名, 从第7位开始取16位
        return substr(md5($str), 7, 16);
    }
}

if (!function_exists('des_encrypt')) {
    /**
     * des加密
     *
     * @param $str
     * @param $key
     *
     * @return mixed|string
     */
    function des_encrypt($str, $key)
    {
        $block = @mcrypt_get_block_size('des', 'ecb');
        $pad   = $block - (strlen($str) % $block);
        $str   .= str_repeat(chr($pad), $pad);
        return safe_b64encode(@mcrypt_encrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB));
    }
}

if (!function_exists('des_decrypt')) {
    /**
     * des解密
     *
     * @param $str
     * @param $key
     *
     * @return bool|string
     */
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
    /**
     * 处理特殊字符
     *
     * @param $string
     *
     * @return mixed|string
     */
    function safe_b64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(['+', '/', '='], ['-', '_', ''], $data);
        return $data;
    }
}

if (!function_exists('safe_b64decode')) {
    /**
     * 解析特殊字符
     *
     * @param $string
     *
     * @return bool|string
     */
    function safe_b64decode($string)
    {
        $data = str_replace(['-', '_'], ['+', '/'], $string);
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
     *
     * @param $number string
     *
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

if (!function_exists('dateRange')) {
    /**
     * 获取指定时间范围内的粒度为 天 的倍数的 指定格式 的数据集合
     *
     * @param        $start
     * @param        $end
     * @param int    $skip
     * @param string $format
     *
     * @return array
     * @throws Exception
     */
    function dateRange($start, $end, $skip = 1, $format = 'Y-m-d')
    {
        if ($start > $end) {
            throw new Exception('起始时间必须小于等于结束时间');
        }

        return array_map(function ($item) use ($format) {
            return date($format, $item);
        }, range(strtotime($start), strtotime($end), $skip * 60 * 60 * 24));
    }
}

if (!function_exists('hourRange')) {
    /**
     * 获取指定时间范围内的 粒度为 分钟 的 指定格式的数据集合
     *
     * @param string $start
     * @param string $end
     * @param int    $skip
     * @param string $format
     *
     * @return array
     * @throws Exception
     */
    function hourRange($start = '00:00', $end = '23:30', $skip = 30, $format = 'H:i')
    {
        if($start > $end) {
            throw new Exception('起始时间必须小于等于结束时间');
        }

        return array_map(function ($item) use ($format) {
            return date($format, $item);
        }, range(strtotime($start), strtotime($end), $skip * 60));
    }
}

if (!function_exists('arrayMergeRecursive')) {
    /**
     * 递归合并数组（支持数字键数组）
     *
     * @param $target
     * @param $source
     *
     * @return mixed
     */
    function arrayMergeRecursive($target, $source)
    {
        $tKeys = array_keys($target);
        $sKeys = array_keys($source);

        $keys = array_unique(array_merge($tKeys, $sKeys));

        foreach ($keys as $key) {
            if (array_key_exists($key, $source) && !array_key_exists($key, $target)) {
                $target[$key] = $source[$key];
            } elseif (array_key_exists($key, $source) && array_key_exists($key, $target)) {
                if (is_array($target[$key]) && is_array($source[$key])) {
                    $target[$key] = arrayMergeRecursive($target[$key], $source[$key]);
                } else {
                    $target[$key] = [$target[$key], $source[$key]];
                }
            }
        }
        return $target;
    }
}

if (!function_exists('snakeCompact')) {
    /**
     * 类似于 compact ，将传入变量名的变量转换为数组，数组的键由驼峰式转换为下划线
     *
     * @param mixed ...$var
     *
     * @return array
     */
    function snakeCompact(...$var)
    {
        if (empty($var)) {
            return [];
        }

        $res = [];
        foreach ($var as $item) {
            $name       = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
                return '_' . strtolower($matches[0]);
            }, $item);
            $res[$name] = $GLOBALS[$item];
        }
        return $res;
    }
}

if (!function_exists('snakeExtract')) {
    function snakeExtract(array $arr)
    {
        foreach ($arr as $key => $value) {
            $name           = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
                return strtoupper($matches[2]);
            }, $key);
            $GLOBALS[$name] = $value;
        }
    }
}

if (!function_exists('getExcelArray')) {
    function getExcelArray($data)
    {
        $timeArray = hourRange();

        $table = [];

        $table[] = $timeArray;
        array_unshift($table[0], "日期-时间");

        $data = array_map(function ($value) {
            return array_column($value, 1, 0);
        }, $data);

        foreach ($data as $key => $value) {
            $column   = [];
            $column[] = $key;
            foreach ($timeArray as $item) {
                $column[] = $value[$item] ?? '-';
            }
            $table[] = $column;
        }

        return $table;
    }
}

if (!function_exists('intToChr')) {
    function intToChr($index, $start = 65)
    {
        $str = '';
        if (floor($index / 26) > 0) {
            $str .= intToChr(floor($index / 26) - 1);
        }
        return $str . chr($index % 26 + $start);
    }
}

if (!function_exists('getTodayTimeOrFullTime')) {
    function getTodayTimeOrFullTime(int $timeStamp){
        return ($timeStamp > strtotime(date("Y-m-d")))
            ? date("H:i:s",$timeStamp)
            : date("Y-m-d H:i:s",$timeStamp);
    }
}

/**
 * @param string $dayTime Y H:i:s
 * @return false|int
 */
if (!function_exists('getTodayTimeStamp')) {
    function getTodayTimeStamp(string $dayTime){
        return strtotime(sprintf("%s %s",date("Y-m-d"),$dayTime))-strtotime(date("Y-m-d"));
    }
}


/**
 * @param string $dayTime Y H:i:s
 * @return false|int
 */
if (!function_exists('ArrGet')) {
    function ArrGet($data,string $column,$default=""){
        return $data[$column]??$default;
    }
}
