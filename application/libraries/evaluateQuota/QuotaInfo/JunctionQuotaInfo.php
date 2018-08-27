<?php
/**
 * 路口级别指标
 */


class JunctionQuotaInfo extends QuotaInfo
{
    /*
       * 停车延误
       * */
    private $_stopDelay;

    /*
     * 排队长度
     * */
    private $_queueLength;

    private $__durationDelay;

    public function getQueueLength($key=null, $weight=null,$gather=false)
    {
        if($key === null){
            return $this->_queueLength;
        }
        return self::getQuotaData($this->_queueLength,$key,$weight,$gather,'stop_delay');
    }

    public function setQueueLength($data, $key=null)
    {
        if($key === null){
            return $this->_queueLength = $data;
        }
        $this->_queueLength = self::setQuotaData($data,$key);
    }

    public function getStopDelay($key=null, $weight=null,$gather=false)
    {
        if($key === null){
            return $this->_stopDelay;
        }
        return self::getQuotaData($this->_stopDelay,$key,$weight,$gather,'stop_delay');

    }

    public function setStopDelay($data, $key=null)
    {
        if($key === null){
            return $this->_stopDelay = $data;
        }
        $this->_stopDelay = self::setQuotaData($data,$key);

    }


    public function getDurationDelay($type=1)
    {
        //自定义返回类型
        return $this->_durationDelay;

    }

    public function setDurationDelay($data,$start_time,$end_time)
    {

        //第二次自定义逻辑

        $this->_durationDelay = array(
            'data'=>$data,
            'a'=>$start_time,
            'b'=>$end_time
        );

    }



}