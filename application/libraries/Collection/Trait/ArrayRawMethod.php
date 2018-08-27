<?php
/**
 * Created by PhpStorm.
 * User: LiCxi
 * Date: 2018/8/26
 * Time: 22:27
 */

trait ArrayRawMethod
{
    private $data = [];

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public function arrayChangeKeyCase($case = CASE_LOWER)
    {
        return new static(array_change_key_case($this->data, $case));
    }

    public function arrayChunk($size, $preserveKey = false)
    {
        return new static(array_chunk($this->data, $size, $preserveKey));
    }

    public function arrayColumn($columnKey, $indexKey = null)
    {
        return new static(array_column($this->data, $columnKey, $indexKey));
    }

    public function arrayCombine($values)
    {
        return new static(array_combine($this->data, $values));
    }

    public function arrayCountValues()
    {
        return new static(array_count_values($this->data));
    }

    public function arrayDiff($array, $_ = null)
    {
        return new static(array_diff($this->data, $array, $_));
    }

    public function arrayDiffAssoc($array, $_ = null)
    {
        return new static(array_diff_assoc($this->data, $array, $_));
    }

    public function arrayDiffKey($array, $_ = null)
    {
        return new static(array_diff_key($this->data, $array, $_));
    }

    public function arrayDiffUassoc($array, $keyCompareFunc, ...$_)
    {
        array_unshift($_, $this->data, $array);
        array_push($_, $keyCompareFunc);
        return new static(call_user_func_array('array_diff_uassoc', $_));
    }

    public function arrayDiffUkey($array, $keyCompareFunc, ...$_)
    {
        array_unshift($_, $this->data, $array);
        array_push($_, $keyCompareFunc);
        return new static(call_user_func_array('array_diff_ukey', $_));
    }

    public function arrayFill($start_index, $num, $value)
    {
        return new static(array_fill($start_index, $num, $value));
    }

    public function arrayFileKeys($keys, $value)
    {
        return new static(array_fill_keys($keys, $value));
    }

    public function arrayFilter($callback = null, $flag = 0)
    {
        return new static(array_filter($this->data, $callback, $flag));
    }

    public function arrayFlip()
    {
        return new static(array_flip($this->data));
    }

    public function arrayIntersectAssoc($array, ...$_)
    {
        return new static(array_intersect_assoc($this->data, $array, ...$_));
    }

    public function arrayIntersectKey($array, ...$_)
    {
        return new static(array_intersect_key($this->data, $array, ...$_));
    }

    public function arrayIntersectUassoc($array, $keyCompareFunc ,...$_)
    {
        array_unshift($_, $this->data, $array);
        array_push($_, $keyCompareFunc);
        return new static(call_user_func_array('array_intersect_uassoc', $_));
    }

    public function arrayIntersectUkey($array, $keyCompareFunc , ...$_)
    {
        array_unshift($_, $this->data, $array);
        array_push($_, $keyCompareFunc);
        return new static(call_user_func_array('array_intersect_ukey', $_));
    }

    public function arrayIntersect($array)
    {
        return new static(array_intersect($this->data, $array));
    }

    public function arrayKeyExists($key)
    {
        return array_key_exists($key, $this->data);
    }

    public function arrayKeyFirst()
    {
        if(function_exists('array_key_first'))
            return array_key_first($this->data);

        $keys = array_keys($this->data);
        return reset($keys);
    }

    public function arrayKeyLast()
    {
        if(function_exists('array_key_last'))
            return array_key_last($this->data);

        $keys = array_keys($this->data);
        return end($keys);
    }

    public function arrayKeys($searchValue = null, $strict = false)
    {
        return new static(array_keys($this->data, $searchValue, $strict));
    }

    public function arrayMap($callback, ...$_)
    {
        return new static(array_map($callback, $this->data, ...$_));
    }

    public function arrayMergeRecursive(...$_)
    {
        return new static(array_merge_recursive($this->data, ...$_));
    }

    public function arrayMerge(...$_)
    {
        return new static(array_merge($this->data, ...$_));
    }

    public function arrayMultisort($arraySortOrder = SORT_ASC, $arraySortFlags = SORT_REGULAR, ...$_)
    {
        array_multisort($this->data, $arraySortOrder, $arraySortFlags, ...$_);
        return $this;
    }

    public function arrayPad($size, $value)
    {
        return new static(array_pad($this->data, $size, $value));
    }

    public function arrayPop()
    {
        array_pop($this->data);
        return $this;
    }

    public function arrayProduct()
    {
        return array_product($this->data);
    }

    public function arrayPush($value, ...$_)
    {
        array_push($this->data, $value, ...$_);
        return $this;
    }

    public function arrayRand($num = 1)
    {
        return $num >= 2 ?
            new static(array_rand($this->data, $num)) :
            array_rand($this->data, $num);
    }

    public function arrayReduce($callback, $initial = null)
    {
        return array_reduce($this->data, $callback, $initial);
    }

    public function arrayReplaceRecursive($array, ...$_)
    {
        return new static(array_replace_recursive($this->data, $array, ...$_));
    }

    public function arrayReplace($array, ...$_)
    {
        return new static(array_replace($this->data, $array, ...$_));
    }

    public function arrayReverse($preserveKeys = false)
    {
        return new static(array_reverse($this->data, $preserveKeys));
    }

    public function arraySearch($needle, $strict = false)
    {
        return array_search($needle, $this->data, $strict);
    }

    public function arrayShift()
    {
        array_shift($this->data);
        return $this;
    }

    public function arraySlice($offset, $length = null, $preserveKeys = false)
    {
        return new static(array_slice($this->data, $offset, $length, $preserveKeys));
    }

    public function arraySplice($offset, $length = null, $replacement = [])
    {
        return new static(array_splice($this->data, $offset, $length, $replacement));
    }

    public function arraySum()
    {
        return array_sum($this->data);
    }

    public function arrayUdiffAssoc($array, $valueCompareFunc, ...$_)
    {
        array_unshift($_, $this->data, $array);
        array_push($_, $valueCompareFunc);
        return new static(call_user_func_array('array_udiff_assoc', $_));
    }

    public function arrayUdiffUAssoc($array, $valueCompareFunc, $keyCompareFunc, ...$_)
    {
        array_unshift($_, $this->data, $array);
        array_push($_, $valueCompareFunc, $keyCompareFunc);
        return new static(call_user_func_array('array_udiff_uassoc', $_));
    }

    public function arrayUdiff($array, $valueCompareFunc, ...$_)
    {
        array_unshift($_, $this->data, $array);
        array_push($_, $valueCompareFunc);
        return new static(call_user_func_array('array_udiff', $_));
    }

    public function arrayUintersectAssoc($array, $valueCompareFunc, ...$_)
    {
        array_unshift($_, $this->data, $array);
        array_push($_, $valueCompareFunc);
        return new static(call_user_func_array('array_uintersect_assoc', $_));
    }

    public function arrayUintersectUassoc($array, $valueCompareFunc, $keyCompareFunc, ...$_)
    {
        array_unshift($_, $this->data, $array);
        array_push($_, $valueCompareFunc, $keyCompareFunc);
        return new static(call_user_func_array('array_uintersect_uassoc', $_));
    }

    public function arrayUintersect ($array, $valueCompareFunc, ...$_)
    {
        array_unshift($_, $this->data, $array);
        array_push($_, $valueCompareFunc);
        return new static(call_user_func_array('array_uintersect', $_));
    }

    public function arrayUnique()
    {
        return new static(array_unique($this->data));
    }

    public function arrayUnshift($value, ...$_)
    {
        array_unshift($this->data, $value, ...$_);
        return $this;
    }

    public function arrayValues()
    {
        return new static(array_values($this->data));
    }

    public function arrayWalkRecursive($callback, $userdata = null)
    {
        array_walk_recursive($this->data, $callback, $userdata);
        return $this;
    }

    public function arrayWalk($callback, $userdata = null)
    {
        array_walk($this->data, $callback, $userdata);
        return $this;
    }

    public function arsort($sortFlags = SORT_REGULAR)
    {
        arsort($this->data, $sortFlags);
        return $this;
    }

    public function asort($sortFlags = SORT_REGULAR)
    {
        asort($this->data, $sortFlags);
        return $this;
    }

    public static function compact(...$_)
    {
        return new static(compact(...$_));
    }

    public function count($mode = COUNT_NORMAL)
    {
        return count($this->data, $mode);
    }

    public function end()
    {
        return end($this->data);
    }

    public function empty()
    {
        return empty($this->data);
    }

    public function inArray($needle, $strict = false)
    {
        return in_array($needle, $this->data, $strict);
    }

    public function keyExists($key)
    {
        return key_exists($key, $this->data);
    }

    public function krsort($sortFlags = SORT_REGULAR)
    {
        krsort($this->data, $sortFlags);
        return $this;
    }

    public function ksort($sortFlags = SORT_REGULAR)
    {
        ksort($this->data, $sortFlags);
        return $this;
    }

    public function natcasesort()
    {
        natcasesort($this->data);
        return $this;
    }

    public function natsort()
    {
        natsort($this->data);
        return $this;
    }

    public static function range($start, $end, $step = 1)
    {
        return new static(range($start, $end, $step));
    }

    public function reset()
    {
        return reset($this->data);
    }

    public function rsort($sortFlags = SORT_REGULAR)
    {
        rsort($this->data, $sortFlags);
        return $this;
    }

    public function shuffle()
    {
        shuffle($this->data);
        return $this;
    }

    public function sort($sortFlags = SORT_REGULAR)
    {
        sort($this->data, $sortFlags);
        return $this;
    }

    public function uasort($valueCompareFunc)
    {
        uasort($this->data, $valueCompareFunc);
        return $this;
    }

    public function uksort($keyCompareFunc)
    {
        uksort($this->data, $keyCompareFunc);
        return $this;
    }

    public function usort($valueCompareFunc)
    {
        usort($this->data, $valueCompareFunc);
        return $this;
    }
}
