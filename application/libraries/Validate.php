<?php
/**********************************************************
 * 简易参数验证器
 *
 * -------------------------------------------------------
 * |暂支持:                                                      |
 * |    nullunable   :校验字符串、数组不能为空                      |
 * |    min:value    :校验数字类型大于最小值、字符串长度大于最小值      |
 * |    max:value    :校验数字类型小于最大值、字符串长度小于最大值      |
 * |    after:value  :校验日期（时间戳）在给定日期（时间戳）之后      |
 * |    before:value :校验日期（时间戳）在给定日期（时间戳）之前      |
 * -------------------------------------------------------
 * user:ningxiangbing@didichuxing.com
 * date:2018-01-30
 **********************************************************/

class Validate
{
    private static $cfg_message = [
        'nullunable' => 'The :paramname 不能为空.',
        'min' => [
            'numeric' => '参数 :paramname 必须大于 :min.',
            'string' => '参数 :paramname 长度必须大于 :min.',
        ],
        'max' => [
            'numeric' => '参数 :paramname 必须小于 :max.',
            'string' => '参数 :paramname 长度必须小于 :max.',
        ],
        'before' => '参数 :paramname 必须在 :before 之前.',
        'after' => '参数 :paramname 必须在 :after 之后.',
        'date' => '参数 :paramname 格式必须为 :date'
    ];

    private static $custom_msg = '';

    /**
     * 校验参数
     * @param array $data 校验数组
     * @param array rules 规则数组
     * @param array|string custom_msg 自定义错误信息
     * @return array ['status'=>true/false, 'errmsg'=>'']
     */
    public static function make(&$data, $rules, $custom_msg = '')
    {
        if (empty($data)) {
            return ['status' => false, 'errmsg' => '请传递参数.'];
        }

        if (count($rules) < 1) {
            return ['status' => false, 'errmsg' => '校验规则不存在.'];
        }

        $diff = array_diff_key($rules, $data);
        if (count($diff) >= 1) {
            foreach ($diff as $k => $v) {
                return ['status' => false, 'errmsg' => '参数 ' . html_escape($k) . ' 错误.'];
            }
        }

        self::$custom_msg = $custom_msg;

        // 循环规则
        foreach ($rules as $k => $v) {
            $temprules = explode('|', $v);
            $stayValue = array_key_exists($k, $data) ? $data[$k] : '';
            foreach ($temprules as $kk => $vv) {
                switch ($vv) {
                    // nullunable 不为空
                    case 'nullunable':
                        $res = self::nullunable($k, $stayValue);
                        if (!$res['status']) {
                            return ['status' => false, 'errmsg' => $res['errmsg']];
                        }
                        break;
                    // min:value 最小value
                    case strstr($vv, 'min:') !== false:
                        $res = self::min($k, $stayValue, $vv);
                        if (!$res['status']) {
                            return ['status' => false, 'errmsg' => $res['errmsg']];
                        }
                        break;
                    // max:vallue 最大value
                    case strstr($vv, 'max:') !== false:
                        $res = self::max($k, $stayValue, $vv);
                        if (!$res['status']) {
                            return ['status' => false, 'errmsg' => $res['errmsg']];
                        }
                        break;
                    // after:date 在给定的日期之后
                    case strstr($vv, 'after:') !== false:
                        $res = self::after($k, $stayValue, $vv);
                        if (!$res['status']) {
                            return ['status' => false, 'errmsg' => $res['errmsg']];
                        }
                        break;

                    // after:date 在给定的日期之后
                    case strstr($vv, 'before:') !== false:
                        $res = self::before($k, $stayValue, $vv);
                        if (!$res['status']) {
                            return ['status' => false, 'errmsg' => $res['errmsg']];
                        }
                        break;

                    // after:date 在给定的日期之后
                    case strstr($vv, 'date:') !== false:
                        $res = self::date($k, $stayValue, $vv);
                        if (!$res['status']) {
                            return ['status' => false, 'errmsg' => $res['errmsg']];
                        }
                        break;
                    default:
                        $errmsg = 'Unknown rules.';
                        return ['status' => false, 'errmsg' => $errmsg];
                        break;
                }
            }
        }

        return ['status' => true];
    }

    /**
     * 判断不为空
     * @param string $param 参数名
     * @param mixed $value 参数值
     * @return array ['status'=>true/false, 'errmsg'=>'']
     */
    private static function nullunable($param, $value)
    {
        if (is_object($value)) {
            return ['status' => false, 'errmsg' => '参数 ' . $param . ' 是一个对象.'];
        } elseif (is_array($value)) {
            if (count($value) < 1) {
                return ['status' => false, 'errmsg' => self::combinErrmsg('nullunable', $param)];
            }
        }
        if (strval($value) === '') {
            return ['status' => false, 'errmsg' => self::combinErrmsg('nullunable', $param)];
        }

        return ['status' => true];
    }

    /**
     * 组合错误信息
     * @param string $rule 验证规则名称
     * @param string|int $param 参数名
     * @param array|string $subrule 二级验证规则
     * @param array|string $value 规则值
     * @return string
     */
    private static function combinErrmsg($rule, $param, $subrule = '', $value = '')
    {
        $errmsg = null;

        if (!empty(self::$custom_msg[$param])) {
            $errmsg = self::$custom_msg[$param];
        } else {
            if (empty($subrule)) {
                $errmsg = str_replace(':paramname', $param, self::$cfg_message[$rule]);
            } else {
                $errmsg = str_replace(':paramname', $param, self::$cfg_message[$rule][$subrule]);
            }

            if (!empty($value) || $value >= 0) {
                $errmsg = str_replace(':' . $rule, $value, $errmsg);
            }
        }

        return $errmsg;
    }

    /**
     * min:value 验证字段必须大于最小值
     * @param string $param 参数名
     * @param mixed $value 参数值
     * @param string $rule min:value
     * @return array ['status'=>true/false, 'errmsg'=>'']
     */
    private static function min($param, $value, $rule)
    {
        $rule_arr = explode(':', $rule);

        if (is_numeric($value)) {
            if ($value < (int)$rule_arr[1]) {
                return ['status' => false, 'errmsg' => self::combinErrmsg('min', $param, 'numeric', $rule_arr[1])];
            }
        } elseif (is_string($value)) {
            if (mb_strlen($value) < (int)$rule_arr[1]) {
                return ['status' => false, 'errmsg' => self::combinErrmsg('min', $param, 'string', $rule_arr[1])];
            }
        } elseif (is_array($value)) {
            if (count($value) < (int)$rule_arr[1]) {
                return ['status' => false, 'errmsg' => self::combinErrmsg('min', $param, 'array', $rule_arr[1])];
            }
        } else {
            return ['status' => false, 'errmsg' => '暂不支持其它类型数据的校验.'];
        }

        return ['status' => true];
    }

    /**
     * max:value 验证字段必须小于最大值
     * @param string $param 参数名
     * @param mixed $value 参数值
     * @param string $rule max:value
     * @return array ['status'=>true/false, 'errmsg'=>'']
     */
    private static function max($param, $value, $rule)
    {
        $rule_arr = explode(':', $rule);

        if (is_numeric($value)) {
            if ($value > (int)$rule_arr[1]) {
                return ['status' => false, 'errmsg' => self::combinErrmsg('max', $param, 'numeric', $rule_arr[1])];
            }
        } elseif (is_string($value)) {
            if (mb_strlen($value) > (int)$rule_arr[1]) {
                return ['status' => false, 'errmsg' => self::combinErrmsg('max', $param, 'string', $rule_arr[1])];
            }
        } else {
            return ['status' => false, 'errmsg' => '暂不支持其它类型数据的校验.'];
        }

        return ['status' => true];
    }

    /**
     * after:value 验证字段在给定日期之后
     * @param string $param 参数名
     * @param mixed $value 参数值
     * @param string $rule after:value
     * @return array ['status'=>true/false, 'errmsg'=>'']
     */
    private static function after($param, $value, $rule)
    {
        $rule_arr = explode(':', $rule);

        if ((int)$value - (int)$rule_arr[1] <= 0) {
            return ['status' => false, 'errmsg' => self::combinErrmsg('after', $param, '', date('Y-m-d', $rule_arr[1]))];
        }

        return ['status' => true];
    }

    /**
     * before:value 验证字段在给定日期之前
     * @param string $param 参数名
     * @param mixed $value 参数值
     * @param string $rule before:value
     * @return array ['status'=>true/false, 'errmsg'=>'']
     */
    private static function before($param, $value, $rule)
    {
        $rule_arr = explode(':', $rule);
        if ((int)$value >= $rule_arr[1]) {
            return ['status' => false, 'errmsg' => self::combinErrmsg('before', $param, '', date('Y-m-d', $rule_arr[1]))];
        }

        return ['status' => true];
    }

    private static function date($param, $value, $rule)
    {
        $rule_arr = explode(':', $rule);
        if(date($rule_arr[1], strtotime($value)) !== $value) {
            return ['status' => false, 'errmsg' => self::combinErrmsg('date', $param, '', $rule_arr[1])];
        }

        return ['status' => true];
    }
}