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

if(!function_exists('_isAssocArray')) {
    function _isAssocArray($arr)
    {
        $index = 0;
        foreach (array_keys($arr) as $key) {
            if (!is_numeric($key) || $index++ !== $key) return false;
        }
        return true;
    }
}

if(!function_exists('d2')) {
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
            if(_isAssocArray($array)) {
                foreach ($array as $key => $value) {
                    echo $tab, "    ";
                    d2($value,$tab."    ");
                }
            } else {
                foreach ($array as $key => $value) {
                    echo $tab, "    ", is_string($key) ? "\"$key\"" : $key, " => ";
                    d2($value,$tab."    ");
                }
            }

            echo $tab, ']', PHP_EOL;
        }

    }
}

if(!function_exists('dd')) {
    function dd($array)
    {
        d2($array);
        die();
    }
}

if(!function_exists('dump')) {
    function dump($array)
    {
        d2($array);
        return PHP_EOL;
    }
}