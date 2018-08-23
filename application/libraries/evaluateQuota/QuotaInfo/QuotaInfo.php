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
    public function formatQuotaChartData($series)
    {
        $chartData = array();
        foreach ($series as $dk => $dv){
            foreach ($dv as $k => $v){
                $chartData[$dk][] = array($k,$v['sum']/$v['count']);
            }
        }
        return $chartData;
    }

    public function getQuotaData($data,$key,$weight,$gather,$quotaName)
    {
        $finalData = array();
        foreach ($data as $value){
            foreach ($value as $k => $v){
                if($gather){
                    $finalData['total'][$k]['sum'] += $v[$quotaName]*$v[$weight];
                }
                if($weight){
                    $finalData[$key][$k]['sum'] += $v[$quotaName]*$v[$weight];
                    $finalData[$key][$k]['count'] += $v[$weight];
                }else{
                    $finalData[$key][$k]['sum'] += $v[$quotaName];
                    $finalData[$key][$k]['count'] += 1;
                }

            }
        }

        return $finalData;
    }

    public function setQuotaData($data,$key)
    {
        $finalData = array();
        foreach ($data as $k => $v){
            $finalData[$v[$key]][] = $v;
        }
        return $finalData;
    }


}