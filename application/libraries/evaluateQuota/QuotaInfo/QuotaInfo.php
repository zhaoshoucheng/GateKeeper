<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/5/14
 * Time: 下午4:28
 */
//抽象指标


abstract class QuotaInfo
{


    /**
     * 返回总结性语言,可重载
     */
    protected function getStandards()
    {

    }

    /**
     * 通用性方法,可重载
     */
    protected function formatQuotaChartData($series)
    {
        $x_axis = array();
        for($i = 0; $i < 86400; $i += 30*60) {
            $hour = intval($i / 3600) % 24;
            $minute = intval($i % 3600 / 60);
            $hour = ($hour > 9) ? $hour : "0{$hour}";
            $minute = ($minute > 9) ? $minute : "0{$minute}";
            $x_axis[] = "{$hour}:{$minute}";
        }
        return array(
            "xAxis"  => isset($series[0]['xValues']) ? $series[0]['xValues'] : $x_axis,
            "series" => $series,
            "base_series" => array(),
        );
    }


}