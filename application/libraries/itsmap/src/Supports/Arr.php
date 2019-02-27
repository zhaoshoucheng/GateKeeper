<?php

namespace Didi\Cloud\ItsMap\Supports;

/*
 * 提供数组相关操作
 */
class Arr
{

    /*
     * 判断两个数组是否是完全一样
     */
    public static function same($arr1, $arr2)
    {
        return empty(array_diff($arr1, $arr2)) && empty(array_diff($arr2, $arr1));
    }

    /*
     * 将数组排序，加上逗号，放到里面。
     */
    public static function arr2str($arr)
    {
        sort($arr);
        return implode(',', $arr);
    }
}

