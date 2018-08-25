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

    }





}