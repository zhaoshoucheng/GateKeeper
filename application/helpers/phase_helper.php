<?php
define('STRAIGHT', 1); // 直行
define('TURN_LEFT', 2); // 左转
define('TURN_RIGHT', 3); // 右转
define('TURN_ROUND', 4); // 掉头

define('EAST', 1);
define('SOUTH', 2);
define('WEST', 3);
define('NORTH', 4);
define('SOUTH_EAST', 5);
define('SOUTH_WEST', 6);
define('NORTH_EAST', 7);
define('NORTH_WEST', 8);

define('EAST_TURN_LEFT', 1);
define('WEST_STRAIGHT', 2);
define('SOUTH_TURN_LEFT', 3);
define('NORTH_STRAIGHT', 4);
define('WEST_TURN_LEFT', 5);
define('EAST_STRAIGHT', 6);
define('NORTH_TURN_LEFT', 7);
define('SOUTH_STRAIGHT', 8);
define('WEST_TURN_RIGHT', 9);
define('NORTH_TURN_RIGHT', 10);
define('EAST_TURN_RIGHT', 11);
define('SOUTH_TURN_RIGHT', 12);
define('WEST_TURN_ROUND', 13);
define('NORTH_TURN_ROUND', 14);
define('EAST_TURN_ROUND', 15);
define('SOUTH_TURN_ROUND', 16);
define('SOUTH_WEST_TURN_LEFT', 17);
define('NORTH_WEST_TURN_LEFT', 18);
define('NORTH_EAST_TURN_LEFT', 19);
define('SOUTH_EAST_TURN_LEFT', 20);
define('SOUTH_WEST_STRAIGHT', 21);
define('NORTH_WEST_STRAIGHT', 22);
define('NORTH_EAST_STRAIGHT', 23);
define('SOUTH_EAST_STRAIGHT', 24);
define('SOUTH_WEST_TURN_RIGHT', 25);
define('NORTH_WEST_TURN_RIGHT', 26);
define('NORTH_EAST_TURN_RIGHT', 27);
define('SOUTH_EAST_TURN_RIGHT', 28);
define('SOUTH_WEST_TURN_ROUND', 29);
define('NORTH_WEST_TURN_ROUND', 30);
define('NORTH_EAST_TURN_ROUND', 31);
define('SOUTH_EAST_TURN_ROUND', 32);

if(!function_exists('phase_map')) {
    function phase_map($in, $out)
    {
        if($in < 0 || $in > 360 || $out < 0 || $out > 360)
            return false;
        $direction = phase_direction($in);
        $action = phase_action($in, $out);
        return constant($direction . '_' . $action);
    }
}

if(!function_exists('phase_direction')) {
    function phase_direction($link)
    {
        $BASE = 22.5;

        $link /= $BASE;

        if ($link < 1 || $link >= 15) return 'NORTH';
        elseif ($link >= 1 && $link < 3) return 'NORTH_EAST';
        elseif ($link >= 3 && $link < 5) return 'EAST';
        elseif ($link >= 5 && $link < 7) return 'SOUTH_EAST';
        elseif ($link >= 7 && $link < 9) return 'SOUTH';
        elseif ($link >= 9 && $link < 11) return 'SOUTH_WEST';
        elseif ($link >= 11 && $link < 13) return 'WEST';
        elseif ($link >= 13 && $link < 15) return 'NORTH_WEST';
        return false;
    }
}

if(!function_exists('phase_action')) {
    function phase_action($in, $out)
    {
        //规则
        $rule = [
            -315 => 'STRAIGHT',
            -200 => 'TURN_LEFT',
            -160 => 'TURN_ROUND',
            -45 => 'TURN_RIGHT',
            45  => 'STRAIGHT',
            160 => 'TURN_LEFT',
            200 => 'TURN_ROUND',
            315 => 'TURN_RIGHT',
            360 => 'STRAIGHT',
        ];

        $diff = $in - $out;
        foreach ($rule as $key => $value)
            if($diff <= $key)
                return $value;
        return false;
    }
}


if(!function_exists('phase_name')) {
    function phase_name($phaseId)
    {
        $phaseNames = [
            "1" => "西左",
            "2" => "东直",
            "3" => "北左",
            "4" => "南直",
            "5" => "东左",
            "6" => "西直",
            "7" => "南左",
            "8" => "北直",
            "9" => "东右",
            "10" => "南右",
            "11" => "西右",
            "12" => "北右",
            "13" => "东掉头",
            "14" => "南掉头",
            "15" => "西掉头",
            "16" => "北掉头",
            "17" => "东北左",
            "18" => "东南左",
            "19" => "西南左",
            "20" => "西北左",
            "21" => "东北直",
            "22" => "东南直",
            "23" => "西南直",
            "24" => "西北直",
            "25" => "东北右",
            "26" => "东南右",
            "27" => "西南右",
            "28" => "西北右",
            "29" => "东北掉头",
            "30" => "东南掉头",
            "31" => "西南掉头",
            "32" => "西北掉头",
        ];

        return $phaseNames[$phaseId] ?? 0;
    }
}
