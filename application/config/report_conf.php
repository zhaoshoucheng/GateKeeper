<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/23
 * Time: 上午10:27
 */

defined('BASEPATH') OR exit('No direct script access allowed');

function orderTime($time1, $time2) {
    if ($time1 == "" || $time2 == "") {
        return [$time1, $time2];
    }
    if (strtotime($time1) < strtotime($time2)) {
        return [$time1, $time2];
    } else {
        return [$time2, $time1];
    }
}

$config['quotas'] = [
    'stop_delay' => [
        'name' => '停车延误',
        'unit' => '秒',
        'summary' => function ($a) {
            list($a[0], $a[1]) = orderTime($a[0], $a[1]);
            if (empty($a[0]) || empty($a[1])) {
                return "";
            }
            $format = '%s-%s时段%s方向延误时间最高';
            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '');
        },
        'describe' => function ($a) {
            list($a[2], $a[3]) = orderTime($a[2], $a[3]);
            if (empty($a[2]) || empty($a[3])) {
                $format = '%s路口在评估日期内%s方向延误时间最高。';
                return sprintf($format, $a[0] ?? '', $a[1] ?? '');
            }
            $format = '%s路口在评估日期内%s方向延误时间最高，其中%s-%s时段延误时间最高，需要重点关注。';
            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '', $a[3] ?? '');
        },
        'desc' => [
            1 => '各方向延误时间变化规律',
            2 => '各方向延误时间分析',
        ],
        'round' => function ($v) {
            return round($v, 2);
        },
    ],
    'stop_time_cycle' => [
        'name' => '停车次数', // 指标名称
        'unit' => '次',
        'summary' => function ($a) {
            list($a[0], $a[1]) = orderTime($a[0], $a[1]);
            if (empty($a[0]) || empty($a[1])) {
                return "";
            }
            $format = '%s-%s时段%s方向停车次数最高';
            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '');
        },
        'describe' => function ($a) {
            list($a[2], $a[3]) = orderTime($a[2], $a[3]);
            if (empty($a[2]) || empty($a[3])) {
                $format = '%s路口在评估日期内%s方向停车次数最高。';
                return sprintf($format, $a[0] ?? '', $a[1] ?? '');
            }
            $format = '%s路口在评估日期内%s方向停车次数最高，其中%s-%s时段平均停车次数最多，需要重点关注。';
            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '', $a[3] ?? '');
        },
        'desc' => [
            1 => '各方向平均停车次数时间变化规律',
            2 => '各方向平均停车次数分析',
        ],
        'round' => function ($v) {
            return round($v, 2);
        },
    ],
    'spillover_rate' => [
        'name' => '溢流指标',
        'unit' => '',
        'summary' => function ($a) {
            list($a[0], $a[1]) = orderTime($a[0], $a[1]);
            if (empty($a[0]) || empty($a[1])) {
                return "";
            }
            $format = '%s-%s时段%s方向溢流指数最高';
            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '');
        },
        'describe' => function ($a) {
            list($a[2], $a[3]) = orderTime($a[2], $a[3]);
            if (empty($a[2]) || empty($a[3])) {
                $format = '%s路口在评估日期内%s方向溢流指数最高。';
                return sprintf($format, $a[0] ?? '', $a[1] ?? '');
            }
            $format = '%s路口在评估日期内%s方向溢流指数最高，其中%s-%s时段溢流指数均高于其他方向，需要重点关注。';
            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '', $a[3] ?? '');
        },
        'desc' => [
            1 => '各方向溢流指数时间变化规律',
            2 => '各方向溢流指数分析',
        ],
        'round' => function ($v) {
            return round($v, 5);
        },
    ],
    'queue_length' => [
        'name' => '排队长度',
        'unit' => '米',
        'summary' => function ($a) {
            $format = '%s-%s时段%s方向最大排队长度最长';
            list($a[0], $a[1]) = orderTime($a[0], $a[1]);
            if (empty($a[0]) || empty($a[1])) {
                return "";
            }
            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '');
        },
        'describe' => function ($a) {
            list($a[2], $a[3]) = orderTime($a[2], $a[3]);
            if (empty($a[2]) || empty($a[3])) {
                $format = '%s路口在评估日期内%s方向最大排队长度最长。';
                return sprintf($format, $a[0] ?? '', $a[1] ?? '');
            }
            $format = '%s路口在评估日期内%s方向最大排队长度最长，其中%s-%s时段时段排队长度最长，需要重点关注。';
            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '', $a[3] ?? '');
        },
        'desc' => [
            1 => '各方向最大排队长度时间变化规律',
            2 => '各方向最大排队长度分析',
        ],
        'round' => function ($v) {
            return round($v);
        },
    ],
    'stop_rate' => [
        'name' => '停车比率',
        'unit' => '',
        'summary' => function ($a) {
            $format = '%s-%s时段%s方向停车比率最高';
            list($a[0], $a[1]) = orderTime($a[0], $a[1]);
            if (empty($a[0]) || empty($a[1])) {
                return "";
            }
            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '');
        },
        'describe' => function ($a) {
            list($a[2], $a[3]) = orderTime($a[2], $a[3]);
            if (empty($a[2]) || empty($a[3])) {
                $format = '%s路口在评估日期内%s方向停车比率最高。';
                return sprintf($format, $a[0] ?? '', $a[1] ?? '');
            }
            $format = '%s路口在评估日期内%s方向停车比率最高，其中%s-%s时段停车比率均高于其他方向，需要重点关注。';
            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '', $a[3] ?? '');
        },
        'desc' => [
            1 => '各方向停车比率时间变化规律',
            2 => '各方向停车比率分析',
        ],
        'round' => function ($v) {
            return round($v, 4);
        },
    ],
    'speed' => [
        'name' => '通过速度',
        'unit' => '',
        'summary' => function ($a) {
            $format = '%s-%s时段%s方向通过速度最低';
            list($a[0], $a[1]) = orderTime($a[0], $a[1]);
            if (empty($a[0]) || empty($a[1])) {
                return "";
            }
            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '');
        },
        'describe' => function ($a) {
            list($a[2], $a[3]) = orderTime($a[2], $a[3]);
            if (empty($a[2]) || empty($a[3])) {
                $format = '%s路口在评估日期内%s方向通过速度最低。';
                return sprintf($format, $a[0] ?? '', $a[1] ?? '');
            }
            $format = '%s路口在评估日期内%s方向通过速度最低，其中%s-%s时段通过速度为各个方向通过速度最低，需要重点关注。';

            return sprintf($format, $a[0] ?? '', $a[1] ?? '', $a[2] ?? '', $a[3] ?? '');
        },
        'desc' => [
            1 => '各方向通过速度时间变化规律',
            2 => '各方向通过速度分析',
        ],
        'round' => function ($v) {
            return round($v * 3.6, 2);
        },
    ],
];