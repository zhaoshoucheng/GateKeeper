<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/5/14
 * Time: 下午4:30
 */


class JunctionQuotaInfo extends QuotaInfo
{

    private $_durationDelay;






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