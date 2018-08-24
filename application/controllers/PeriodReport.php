<?php
/**
 * 周报、月报模块
 */

class PeriodReport extends MY_Controller
{

    const WEEK = 1;
    const MONTH = 2;

    const ALLDAY = 1;
    const MORNING = 2;
    const NIGHT = 3;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('period_model');
        $this->load->library('EvaluateQuota');
    }

    private static function quotasort($a,$b){
        return $b[1]-$a[1];

    }

    /**
     * 周、月报–市运行情况概述信息
     */
    public function overview()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'city_id'     => 'nullunable',
                'city_name' => 'nullunable',
                'type'      => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }
        $cityId = $params['city_id'];
        $type = $params['type'];
        $cityName = $params['city_name'];

        if($type == self::WEEK){//周报
            $lastTime = $this->getLastWeek();
            $prelastTime = $this->getLastWeek(2);
            $lastData = $this->period_model->getCityWeekData($cityId,$lastTime['start_time']);
            $prelastData = $this->period_model->getCityWeekData($cityId,$prelastTime['start_time']);
        }else{
            $lastTime = $this->getLastMonth();
            $prelastTime = $this->getLastMonth(2);
            $lastData = $this->period_model->getCityMonthData($cityId,explode('-',$lastTime['start_time'])[0],explode('-',$lastTime['start_time'])[1]);
            $prelastData = $this->period_model->getCityMonthData($cityId,explode('-',$prelastTime['start_time'])[0],explode('-',$prelastTime['start_time'])[1]);
        }

        if(empty($lastData)){
            $lastData = [
                'stop_delay'=>0,
                'spillover_freq'=>0,
                'oversaturation_freq'=>0
            ];
        }else{
            $lastData = $lastData[0];
        }
        if(empty($prelastData)){
            $prelastData = [
                'stop_delay'=>0,
                'spillover_freq'=>0,
                'oversaturation_freq'=>0
            ];
        }else{
            $prelastData = $prelastData[0];
        }

        $stop_delay_MoM = ($lastData['stop_delay']-$prelastData['stop_delay'])/($prelastData['stop_delay']==0?1:$prelastData['stop_delay']) * 100;
        $spillover_freq_MoM = ($lastData['spillover_freq']-$prelastData['spillover_freq'])/($prelastData['spillover_freq']==0?1:$prelastData['spillover_freq']) * 100;
        $oversaturation_freq_MoM = ($lastData['oversaturation_freq']-$prelastData['oversaturation_freq'])/($prelastData['oversaturation_freq']==0?1:$prelastData['oversaturation_freq'])* 100;
        if($type == self::WEEK){
            $overviewStr = "本周(".$lastTime['start_time']."-".$lastTime['end_time'].")".$cityName."区拥堵程度相对严重,";
            $change = $stop_delay_MoM > 0 ? "增长":"减少";
            $overviewStr .="市区整体平均延误".$lastData['stop_delay']."秒,环比上周".$change.abs(round($stop_delay_MoM))."%。";
            $change = $spillover_freq_MoM > 0 ? "增长":"减少";
            $overviewStr .="发生溢流路口共".$lastData['spillover_freq']."路口次,环比上周问题路口".$change.abs(round($spillover_freq_MoM))."%。";
            $change = $oversaturation_freq_MoM > 0 ? "增长":"减少";
            $overviewStr .="过饱和路口".$lastData['oversaturation_freq']."路口次,环比上周问题".$change.abs(round($oversaturation_freq_MoM))."%。";

        }else{
            $overviewStr = "本月(".$lastTime['start_time']."-".$lastTime['end_time'].")".$cityName."区拥堵程度相对严重,";
            $change = $stop_delay_MoM > 0 ? "增长":"减少";
            $overviewStr .="市区整体平均延误".$lastData['stop_delay']."秒,环比上月".$change.abs(round($stop_delay_MoM))."%。";
            $change = $spillover_freq_MoM > 0 ? "增长":"减少";
            $overviewStr .="发生溢流路口共".$lastData['spillover_freq']."路口次,环比上月问题路口".$change.abs(round($spillover_freq_MoM))."%。";
            $change = $oversaturation_freq_MoM > 0 ? "增长":"减少";
            $overviewStr .="过饱和路口".$lastData['oversaturation_freq']."路口次,环比上月问题".$change.abs(round($oversaturation_freq_MoM))."%。";
        }
        return $this->response(array(
            'summary'=>$overviewStr
        ));
    }

    /**
     *周、月报–市平均延误运行情况表格
     */
    public function stopDelayTable()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'city_id'     => 'nullunable',
                'type'      => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }
        $cityId = $params['city_id'];
        $type = $params['type'];

        if($type == self::WEEK){
            $lastTime = $this->getLastWeek();
        }else{
            $lastTime = $this->getLastMonth();
        }
        $dateList = self::getDateFromRange($lastTime['start_time'],$lastTime['end_time']);

        $hourDate = $this->period_model->getCityHourData($cityId,$dateList);
        if(empty($hourDate)){
            return $this->response([]);
        }
        $evaluate = new EvaluateQuota();

        $charData = $evaluate->getCityStopDelayAve($hourDate);

        return $this->response(array(
            'info'=>array(
                'start_time'=>$lastTime['start_time'],
                'end_time'=>$lastTime['end_time']
            ),
            'base'=>$charData
        ));

    }

    /**
     *行政区交通运行情况（全天，早高峰，晚高峰 ）
     */
    public function districtReport()
    {

    }

    /**
     *周、月报–延误最大top20(top10)
     */
    public function delayTopJunction()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'city_id'     => 'nullunable',
                'type'      => 'nullunable',
                'time_type'      => 'nullunable',
                'top'      => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }
        $cityId = $params['city_id'];
        $type = $params['type'];
        $timeType = $params['time_type'];
        $topNum = $params['top'];

        if($type == self::WEEK){
            $lastTime = $this->getLastWeek();
        }else{
            $lastTime = $this->getLastMonth();
        }
        if($timeType == self::ALLDAY){
            $hour = null;
        }elseif ($timeType == self::MORNING){
            $hour = array('06:30','07:00','07:30','08:00','08:30','09:00','09:30');
        }else{
            $hour = array('16:30','17:00','17:30','18:00','18:30','19:00','19:30');
        }

        if($timeType == self::ALLDAY && $type == self::WEEK){
            $data = $this->period_model->getJunctionWeekData($cityId,null,$lastTime['start_time'],'stop_delay desc');
        }elseif($timeType == self::ALLDAY && $type == self::MONTH){
            $data = $this->period_model->getJunctionMonthData($cityId,null,explode('-',$lastTime['start_time'])[0],explode('-',$lastTime['start_time'])[1],'stop_delay desc');
        }else{
            $dateList = self::getDateFromRange($lastTime['start_time'],$lastTime['end_time']);
            $data = $this->period_model->getJunctionHourData($cityId,$dateList,$hour,'stop_delay desc');
            $evaluate = new EvaluateQuota();
            $data = $evaluate->getJunctionStopDelayAve($data,false);
            //TODO sort
        }

        $finalData = array();
        foreach ($data as $k => $v){
            $finalData[] = array(
                'rank'=>$k,
                'logic_junction_id' => $v['logic_junction_id'],
                'stop_delay' => $v['stop_delay']
            );
        }

        return $this->response($finalData);


    }

    /**
     *周、月报–排队长度top20(top10)
     */
    public function queueLineTopJunction()
    {

    }

    /**
     *周、月报–溢流问题分析
     */
    public function spilloverChart()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'city_id'     => 'nullunable',
                'type'      => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $cityId = $params['city_id'];
        $type = $params['type'];

        if($type == self::WEEK){
            $lastTime = $this->getLastWeek();
            $prelastTime = $this->getLastWeek(2);
        }else{
            $lastTime = $this->getLastMonth();
            $prelastTime = $this->getLastMonth(2);
        }

        $dateList = self::getDateFromRange($lastTime['start_time'],$lastTime['end_time']);
        $perDateList = self::getDateFromRange($prelastTime['start_time'],$prelastTime['end_time']);
        $lasthourDate = $this->period_model->getCityHourData($cityId,$dateList);
        $prelasthourDate = $this->period_model->getCityHourData($cityId,$perDateList);
        if(empty($lasthourDate) || empty($prelasthourDate)){
            return $this->response([]);
        }
        $evaluate = new EvaluateQuota();
        $lastcharData = $evaluate->getCitySplloverFreq($lasthourDate);
        $preLastcharData = $evaluate->getCitySplloverFreq($prelasthourDate);

        $lastCharMap = [];
        $preLastCharMap = [];
        foreach ($lastcharData['total'] as $lk => $lv){
            $lastCharMap[$lv[0]] = $lv[1];
        }
        foreach ($preLastcharData['total'] as $pk => $pv){
            $preLastCharMap[$pv[0]] = $pv[1];
        }
        $lastData = $lastcharData['total'];
        $preLastData = $preLastcharData['total'];
        usort($lastData, array("PeriodReport","quotasort"));
        usort($preLastData, array("PeriodReport","quotasort"));
        if($type == self::WEEK){
            $lastMoM = ($lastData[0][1] - $preLastCharMap[$lastData[0][0]])/$preLastCharMap[$lastData[0][0]] * 100;
            $preLastMoM = ($preLastData[0][1] - $lastCharMap[$preLastData[0][0]])/$lastCharMap[$preLastData[0][0]] * 100;
            $change = $lastMoM > 0 ? "增加":"减少";
            $summary = "本周溢流问题在".$lastData[0][0]."时段发生最多,为".$lastData[0][1]."个。环比上周".$change.abs(round($lastMoM))."%。";
            $change = $preLastMoM > 0 ? "增加":"减少";
            $summary .= "上周溢流问题在".$preLastData[0][0]."时段发生最多,为".$preLastData[0][1]."个。环比本周".$change.abs(round($preLastMoM))."%。";
        }else{
            $lastMoM = ($lastData[0][1] - $preLastCharMap[$lastData[0][0]])/$preLastCharMap[$lastData[0][0]] * 100;
            $preLastMoM = ($preLastData[0][1] - $lastCharMap[$preLastData[0][0]])/$lastCharMap[$preLastData[0][0]] * 100;
            $change = $lastMoM > 0 ? "增加":"减少";
            $summary = "本月溢流问题在".$lastData[0][0]."时段发生最多,为".$lastData[0][1]."个。环比上月".$change.abs(round($lastMoM))."%。";
            $change = $preLastMoM > 0 ? "增加":"减少";
            $summary .= "上月溢流问题在".$preLastData[0][0]."时段发生最多,为".$preLastData[0][1]."个。环比本月".$change.abs(round($preLastMoM))."%。";
        }

        return $this->response(array(
            'base'=>array(
                'period'=>$lastcharData['total'],
                'last_period'=>$preLastcharData['total']
            ),
            'summary'=>$summary
        ));

    }

    /**
     *周、月报–早(晚)高峰平均延误柱状图
     */
    public function delayChart()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'city_id'     => 'nullunable',
                'type'      => 'nullunable',
                'time_type'      => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $cityId = $params['city_id'];
        $type = $params['type'];
        $timeType = $params['time_type'];

        if($type == self::WEEK){
            $lastTime = $this->getLastWeek();
            $prelastTime = $this->getLastWeek(2);
        }else{
            $lastTime = $this->getLastMonth();
            $prelastTime = $this->getLastMonth(2);
        }

        if($timeType == self::MORNING){
            $hour = array('06:30','07:00','07:30','08:00','08:30','09:00','09:30');
        }else{
            $hour = array('16:30','17:00','17:30','18:00','18:30','19:00','19:30');
        }
        $dateList = self::getDateFromRange($lastTime['start_time'],$lastTime['end_time']);
        $preDataList = self::getDateFromRange($prelastTime['start_time'],$prelastTime['end_time']);
        $data = $this->period_model->getCityHourData($cityId,$dateList,$hour);
        $preData = $this->period_model->getCityHourData($cityId,$preDataList,$hour);
        $evaluate = new EvaluateQuota();
        $lastcharData = $evaluate->getStopDelayAve($data);
        $preLastcharData = $evaluate->getStopDelayAve($preData);
        return $this->response(array(
            'base'=>array(
                'period'=>$lastcharData['total'],
                'last_period'=>$preLastcharData['total']
            ),
            'summary'=>array()
        ));

    }

    /**
     *周、月报–早(晚)高峰平均延误柱状图
     */
    public function speedChart()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'city_id'     => 'nullunable',
                'type'      => 'nullunable',
                'time_type'      => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $cityId = $params['city_id'];
        $type = $params['type'];
        $timeType = $params['time_type'];

        if($type == self::WEEK){
            $lastTime = $this->getLastWeek();
            $prelastTime = $this->getLastWeek(2);
        }else{
            $lastTime = $this->getLastMonth();
            $prelastTime = $this->getLastMonth(2);
        }

        if($timeType == self::MORNING){
            $hour = array('06:30','07:00','07:30','08:00','08:30','09:00','09:30');
        }else{
            $hour = array('16:30','17:00','17:30','18:00','18:30','19:00','19:30');
        }
        $dateList = self::getDateFromRange($lastTime['start_time'],$lastTime['end_time']);
        $preDataList = self::getDateFromRange($prelastTime['start_time'],$prelastTime['end_time']);
        $data = $this->period_model->getCityHourData($cityId,$dateList,$hour);
        $preData = $this->period_model->getCityHourData($cityId,$preDataList,$hour);
        $evaluate = new EvaluateQuota();
        $lastcharData = $evaluate->getCitySpeedAve($data);
        $preLastcharData = $evaluate->getCitySpeedAve($preData);
        return $this->response(array(
            'base'=>array(
                'period'=>$lastcharData['total'],
                'last_period'=>$preLastcharData['total']
            ),
            'summary'=>array()
        ));
    }

    private function getLastWeek($t = 1)
    {

        return array(
            'start_time'=>date('Y-m-d', strtotime((($t+1)*-1).' monday', time())),
            'end_time'=>date('Y-m-d', strtotime((($t)*-1).' sunday', time()))
        );
    }

    private function getLastMonth($t = 1)
    {
        return array(
            'start_time'=>date('Y-m-01', strtotime((($t)*-1).' month')),
            'end_time'=>date('Y-m-t', strtotime((($t)*-1).' month'))
        );
    }

    private function getDateFromRange($startdate, $enddate){

        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);

        // 计算日期段内有多少天
        $days = ($etimestamp-$stimestamp)/86400+1;

        // 保存每天日期
        $date = array();

        for($i=0; $i<$days; $i++){
            $date[] = date('Y-m-d', $stimestamp+(86400*$i));
        }

        return $date;
    }


}