<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/5/14
 * Time: 下午4:20
 */


//具体工厂
class JunctionQuota implements EvaluateQuotaFactory{

    private $_loadData = null;

    public function load_data($data)
    {
        $this->_loadData = $data;
    }

    /**
     * 路口各方向延误时间
     */

    public function getDurationDelay($start_time,$end_time)
    {

        $quotaInfo = new JunctionQuotaInfo();
        //可以做自定义逻辑处理

        $quotaInfo->setDurationDelay($this->_loadData,$start_time,$end_time);

        return $quotaInfo->getDurationDelay();
    }




}