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

if(!function_exists('dd') && !function_exists('d2')) {
    function d2($array, $tab = '')
    {
        if(is_string($array)) {
            echo '"', $array, '"', PHP_EOL;
        } elseif(is_numeric($array)) {
            echo $array, PHP_EOL;
        } elseif(is_object($array)) {
            $array = $array->toArray();
        }
        if(is_array($array)) {
            echo '[', PHP_EOL;
            foreach ($array as $key => $value) {
                echo $tab, "    [", $key, "] => ";
                d2($value,$tab."    ");
            }
            echo $tab, ']', PHP_EOL;
        }

    }

    function dd($array)
    {
        d2($array);
        die();
    }
}