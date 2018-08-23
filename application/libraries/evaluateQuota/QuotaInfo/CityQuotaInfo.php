<?php
/**
 * 城市级别指标
 */


class CityQuotaInfo extends QuotaInfo
{

    /*
     * 停车延误
     * */
    private $_stopDelay;

    /*
     * 平均速度
     * */
    private $_speed;


    /*
     * 溢流次数
     * */
    private $_spillover_freq;

    /*
     * 过饱和次数
     * */
    private $_oversaturation_freq;


    /**
     * @param $data 原始数据
     * @param null $key,分类关键词
     */
    public function setStopDelay($data, $key=null)
    {
        if($key === null){
            return $this->_stopDelay = $data;
        }
        $this->_stopDelay = self::setQuotaData($data,$key);
//        $finalData = array();
//        foreach ($data as $k => $v){
//            $finalData[$v[$key]][] = $v;
//        }
//        $this->_stopDelay = $finalData;
    }

    /**
     * 获取加权平均值
     * @param null $key x轴
     * @param null $weight,权重
     */
    public function getStopDelay($key=null, $weight=null,$gather=false)
    {
        if($key === null){
            return $this->_stopDelay;
        }
        return self::getQuotaData($this->_stopDelay,$key,$weight,$gather,'stop_delay');
//        $finalData = array();
//        foreach ($this->_stopDelay as $key => $value){
//            foreach ($value as $k => $v){
//                if($gather){
//                    $finalData['total'][$k]['sum'] += $v['stop_delay']*$v[$weight];
//                }
//                if($weight){
//                    $finalData[$key][$k]['sum'] += $v['stop_delay']*$v[$weight];
//                    $finalData[$key][$k]['count'] += $v[$weight];
//                }else{
//                    $finalData[$key][$k]['sum'] += $v['stop_delay'];
//                    $finalData[$key][$k]['count'] += 1;
//                }
//
//            }
//        }
//
//        return $finalData;
    }

    /**
     * @param $data 原始数据
     * @param null $key,分类关键词
     */
    public function setSpeed($data, $key=null)
    {
        if($key === null){
            return $this->_speed = $data;
        }
        $this->_speed = self::setQuotaData($data,$key);
//        $finalData = array();
//        foreach ($data as $k => $v){
//            $finalData[$v[$key]][] = $v;
//        }
//        $this->_speed = $finalData;
    }

    /**
     * 获取加权平均值
     * @param null $key x轴
     * @param null $weight,权重
     */
    public function getSpeed($key=null, $weight=null,$gather=false)
    {
        if($key === null){
            return $this->_speed;
        }
        return self::getQuotaData($this->_speed,$key,$weight,$gather,'speed');
//
//        $finalData = array();
//        foreach ($this->_speed as $key => $value){
//            foreach ($value as $k => $v){
//                if($gather){
//                    $finalData['total'][$k]['sum'] += $v['speed']*$v[$weight];
//                }
//                if($weight){
//                    $finalData[$key][$k]['sum'] += $v['speed']*$v[$weight];
//                    $finalData[$key][$k]['count'] += $v[$weight];
//                }else{
//                    $finalData[$key][$k]['sum'] += $v['speed'];
//                    $finalData[$key][$k]['count'] += 1;
//                }
//            }
//        }
//
//        return $finalData;
    }

    /**
     * @param $data 原始数据
     * @param null $key,分类关键词
     */
    public function setSpilloverFreq($data, $key=null)
    {
        if($key === null){
            return $this->_spillover_freq = $data;
        }
        $this->_spillover_freq = self::setQuotaData($data,$key);
//
//        $finalData = array();
//        foreach ($data as $k => $v){
//            $finalData[$v[$key]][] = $v;
//        }
//        $this->_spillover_freq = $finalData;
    }

    /**
     * 获取加权平均值
     * @param null $key x轴
     * @param null $weight,权重
     */
    public function getSpilloverFreq($key=null, $weight=null,$gather=false)
    {
        if($key === null){
            return $this->_spillover_freq;
        }
        return self::getQuotaData($this->_spillover_freq,$key,$weight,$gather,'spillover_freq');
//
//        $finalData = array();
//        foreach ($this->_spillover_freq as $key => $value){
//            foreach ($value as $k => $v){
//                if($gather){
//                    $finalData['total'][$k]['sum'] += $v['spillover_freq']*$v[$weight];
//                }
//                if($weight){
//                    $finalData[$key][$k]['sum'] += $v['spillover_freq']*$v[$weight];
//                    $finalData[$key][$k]['count'] += $v[$weight];
//                }else{
//                    $finalData[$key][$k]['sum'] += $v['spillover_freq'];
//                    $finalData[$key][$k]['count'] += 1;
//                }
//
//            }
//        }
//
//        return $finalData;

    }





}