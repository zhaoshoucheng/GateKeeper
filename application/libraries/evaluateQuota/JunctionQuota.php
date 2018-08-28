<?php
/**
 * 路口指标工厂类
 */


class JunctionQuota implements EvaluateQuotaFactory{

    private $_loadData = null;

    public function load_data($data)
    {
        $this->_loadData = $data;
    }

    /**
     * 延误时间表
     */
    public function getStopDelayAveTable()
    {
        $quotaInfo = new JunctionQuotaInfo();
        $quotaInfo->setStopDelay($this->_loadData,'logic_junction_id');
        $data = $quotaInfo->getStopDelay('date','traj_count',false);
        return $quotaInfo->getAveQuotaData($data);

    }

    /**
     * 排队长度表
     */
    public function getQueueLengthAveTable()
    {
        $quotaInfo = new JunctionQuotaInfo();
        $quotaInfo->setQueueLength($this->_loadData,'logic_junction_id');
        $data = $quotaInfo->getQueueLength('date','traj_count',false);
        return $quotaInfo->getAveQuotaData($data);

    }

    public function getStopDelayAve($trans2Chart=true)
    {
        $quotaInfo = new JunctionQuotaInfo();
        $quotaInfo->setStopDelay($this->_loadData,'logic_junction_id');
        $data = $quotaInfo->getStopDelay('date','traj_count',false);
        if($trans2Chart){
            return $quotaInfo->formatQuotaChartData($data);
        }
        return $data;
    }
    public function getQueueLengthAve($trans2Chart=true)
    {
        $quotaInfo = new JunctionQuotaInfo();
        $quotaInfo->setQueueLength($this->_loadData,'logic_junction_id');
        $data = $quotaInfo->getQueueLength('date','traj_count',false);
        if($trans2Chart){
            return $quotaInfo->formatQuotaChartData($data);
        }
        return $data;
    }

//    /**
//     * 路口各方向延误时间
//     */
//
//    public function getDurationDelay($start_time,$end_time)
//    {
//
//        $quotaInfo = new JunctionQuotaInfo();
//        //可以做自定义逻辑处理
//
//        $quotaInfo->setDurationDelay($this->_loadData,$start_time,$end_time);
//
//        return $quotaInfo->getDurationDelay();
//    }
}