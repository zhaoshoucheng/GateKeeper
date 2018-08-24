<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/24
 * Time: 下午9:13
 */

if(!function_exists('compare')) {
    function compare($compare, $v1, $v2)
    {
        switch ($compare) {
            case '>': return $v1 > $v2;
            case '<': return $v1 < $v2;
            case '=': return $v1 == $v2;
            case '==': return $v1 == $v2;
            case '>=': return $v1 >= $v2;
            case '<=': return $v1 <= $v2;
            case '!=': return $v1 != $v2;
            default: return false;
        }
    }
}