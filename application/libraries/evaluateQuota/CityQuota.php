<?php
/**
 * 路口指标工厂类
 */


class CityQuota implements EvaluateQuotaFactory{

    private $_loadData = null;

    public function load_data($data)
    {
        $this->_loadData = $data;
    }

    /**
     * 城市平均延误
     */
    public function getStopDelayAve($trans2Chart=true)
    {
        $quotaInfo = new CityQuotaInfo();
        $quotaInfo->setStopDelay($this->_loadData,'date');
        $data = $quotaInfo->getStopDelay('hour','traj_count',true);
        if($trans2Chart){
            return $quotaInfo->formatQuotaChartData($data);
        }

        return $data;
    }

    /**
     * 城市平均速度
     */
    public function getSpeedAve($trans2Chart=true)
    {
        $quotaInfo = new CityQuotaInfo();
        $quotaInfo->setSpeed($this->_loadData,'date');
        $data = $quotaInfo->getSpeed('hour','traj_count',true);
        if($trans2Chart){
            return $quotaInfo->formatQuotaChartData($data);
        }

        return $data;
    }

    /**
     * 城市溢流次数
     */
    public function getSplloverFreq($trans2Chart=true)
    {
        $quotaInfo = new CityQuotaInfo();
        $quotaInfo->setSpilloverFreq($this->_loadData,'date');
        $data = $quotaInfo->getSpilloverFreq('hour',null,true);
        if($trans2Chart){
            return $quotaInfo->formatQuotaChartData($data);
        }
        return $data;
    }



}