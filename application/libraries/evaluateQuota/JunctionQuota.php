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
    public function getStopDelayAve($trans2Chart=true)
    {
        $quotaInfo = new JunctionQuotaInfo();
        $quotaInfo->setStopDelay($this->_loadData,'logic_junction_id');
        $data = $quotaInfo->getStopDelay('hour','traj_count',false);
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