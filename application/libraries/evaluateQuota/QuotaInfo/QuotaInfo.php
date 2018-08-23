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
    public function formatQuotaChartData($series,$round=2)
    {
        $chartData = array();
        foreach ($series as $dk => $dv){
            foreach ($dv as $k => $v){
                $chartData[$dk][] = array($k,round($v['sum']/$v['count'],$round));
            }
        }
        return $chartData;
    }

    /**
     * @param $data 原始数据
     * @param $key  x轴
     * @param $weight 权重
     * @param $gather 汇总
     * @param $quotaName 指标关键词(y轴)
     * @return array
     */
    public function getQuotaData($data, $key, $weight, $gather, $quotaName)
    {
        $finalData = [];
        if($gather){
            $finalData['total']=[];
        }
        foreach ($data as $dkey => $value){
            foreach ($value as $k => $v){
                if($gather && !isset($finalData['total'][$v[$key]])){
                    $finalData['total'][$v[$key]]['sum']=0;
                    $finalData['total'][$v[$key]]['count']=0;
                }
                if(!isset($finalData[$dkey][$v[$key]])){
                    $finalData[$dkey][$v[$key]]['sum']=0;
                    $finalData[$dkey][$v[$key]]['count']=0;
                }

                if($weight){
                    $finalData[$dkey][$v[$key]]['sum'] += $v[$quotaName]*$v[$weight];
                    $finalData[$dkey][$v[$key]]['count'] += $v[$weight];
                    if($gather){
                        $finalData['total'][$v[$key]]['sum'] += $v[$quotaName]*$v[$weight];
                        $finalData['total'][$v[$key]]['count'] += $v[$weight];
                    }
                }else{
                    $finalData[$dkey][$v[$key]]['sum'] += $v[$quotaName];
                    $finalData[$dkey][$v[$key]]['count'] += 1;
                    if($gather){
                        $finalData['total'][$v[$key]]['sum'] += $v[$quotaName];
                        $finalData['total'][$v[$key]]['count'] += 1;
                    }
                }

            }
        }
//        foreach ($data as $dkey => $value){
//            foreach ($value as $k => $v){
//                if($gather && !isset($finalData['total'][$v[$key]])){
//                    $finalData['total'][$v[$key]]['sum']=0;
//                    $finalData['total'][$v[$key]]['count']=0;
//                }
//                if(!isset($finalData[$v[$key]][$dkey])){
//                    $finalData[$v[$key]][$dkey]['sum']=0;
//                    $finalData[$v[$key]][$dkey]['count']=0;
//                }
//
//                if($weight){
//                    $finalData[$v[$key]][$dkey]['sum'] += $v[$quotaName]*$v[$weight];
//                    $finalData[$v[$key]][$dkey]['count'] += $v[$weight];
//                    if($gather){
//                        $finalData['total'][$v[$key]]['sum'] += $v[$quotaName]*$v[$weight];
//                        $finalData['total'][$v[$key]]['count'] += $v[$weight];
//                    }
//                }else{
//                    $finalData[$v[$key]][$dkey]['sum'] += $v[$quotaName];
//                    $finalData[$v[$key]][$dkey]['count'] += 1;
//                    if($gather){
//                        $finalData['total'][$v[$key]]['sum'] += $v[$quotaName];
//                        $finalData['total'][$v[$key]]['count'] += 1;
//                    }
//                }
//
//            }
//        }

        return $finalData;
    }

    /**
     * @param $data 原始数据
     * @param $key  分类数据关键词
     * @return array
     */
    public function setQuotaData($data, $key)
    {
        $finalData = array();
        foreach ($data as $k => $v){
            $finalData[$v[$key]][] = $v;
        }

        return $finalData;
    }


}