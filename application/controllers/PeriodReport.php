<?php
/**
 * 周报、月报模块
 */

class PeriodReport extends MY_Controller
{

    const WEEK = 3;
    const MONTH = 4;

    const ALLDAY = 1;
    const MORNING = 2;
    const NIGHT = 3;

    public function __construct()
    {


        parent::__construct();
        $this->load->model('period_model');
        $this->load->library('EvaluateQuota');
        $this->load->model('waymap_model');
    }

    private static function quotasort($a,$b){
        return $b[1]-$a[1];

    }

    private function getMorningHours()
    {
        return array('06:30','07:00','07:30','08:00','08:30','09:00','09:30');
    }

    private function getNightHours()
    {
        return array('16:30','17:00','17:30','18:00','18:30','19:00','19:30');
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
            $overviewStr = "本周(".self::formatTime($lastTime['start_time'])."-".self::formatTime($lastTime['end_time']).")".$cityName."区拥堵程度相对严重,";
            $change = $stop_delay_MoM > 0 ? "增长":"减少";
            $overviewStr .="市区整体平均延误".round($lastData['stop_delay'],2)."秒,环比上周".$change.abs(round($stop_delay_MoM,2))."%。";
            $change = $spillover_freq_MoM > 0 ? "增长":"减少";
            $overviewStr .="发生溢流路口共".$lastData['spillover_freq']."路口次,环比上周问题路口".$change.abs(round($spillover_freq_MoM,2))."%。";
            $change = $oversaturation_freq_MoM > 0 ? "增长":"减少";
            $overviewStr .="过饱和路口".$lastData['oversaturation_freq']."路口次,环比上周问题".$change.abs(round($oversaturation_freq_MoM,2))."%。";

        }else{
            $overviewStr = "本月(".self::formatTime($lastTime['start_time'])."-".self::formatTime($lastTime['end_time']).")".$cityName."区拥堵程度相对严重,";
            $change = $stop_delay_MoM > 0 ? "增长":"减少";
            $overviewStr .="市区整体平均延误".round($lastData['stop_delay'],2)."秒,环比上月".$change.abs(round($stop_delay_MoM,2))."%。";
            $change = $spillover_freq_MoM > 0 ? "增长":"减少";
            $overviewStr .="发生溢流路口共".$lastData['spillover_freq']."路口次,环比上月问题路口".$change.abs(round($spillover_freq_MoM,2))."%。";
            $change = $oversaturation_freq_MoM > 0 ? "增长":"减少";
            $overviewStr .="过饱和路口".$lastData['oversaturation_freq']."路口次,环比上月问题".$change.abs(round($oversaturation_freq_MoM,2))."%。";
        }
        return $this->response(array(
            'summary'=>$overviewStr,
            'start_time'=>self::formatTime($lastTime['start_time']),
            'end_time'=>self::formatTime($lastTime['end_time'])
        ));
    }

    private function formatTime($time)
    {
        return str_replace('-','.',$time);
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
            $prelastTime = $this->getLastWeek(2);
        }else{
            $lastTime = $this->getLastMonth();
            $prelastTime = $this->getLastMonth(2);

        }
        $dateList = self::getDateFromRange($lastTime['start_time'],$lastTime['end_time']);
        $predateList = self::getDateFromRange($prelastTime['start_time'],$prelastTime['end_time']);

        $hourDate = $this->period_model->getCityHourData($cityId,$dateList);
        if(empty($hourDate)){
            return $this->response([]);
        }

        $final_data = [];
        $evaluate = new EvaluateQuota();

        if($type == self::WEEK){//周报8条线
            $final_data = $evaluate->getCityStopDelayAve($hourDate);

        }else{//月报两条线
            $prehourDate = $this->period_model->getCityHourData($cityId,$predateList);

            $charData = $evaluate->getCityStopDelayAve($hourDate);
            $precharData = $evaluate->getCityStopDelayAve($prehourDate);
            $final_data['period'] = $charData['total'];
            $final_data['last_period'] = $precharData['total'];
        }


        return $this->response(array(
            'info'=>array(
                'start_time'=>$lastTime['start_time'],
                'end_time'=>$lastTime['end_time']
            ),
            'base'=>$final_data
        ));

    }

    /**
     *行政区交通运行情况（全天，早高峰，晚高峰 ）
     */
    public function districtReport()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'city_id'     => 'nullunable',
                'type'      => 'nullunable',
                'time_type'      => 'nullunable',
                'city_name'      => 'nullunable',
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
        $cityName = $params['city_name'];
        //获取行政区信息
        $disticts = $this->waymap_model->getDistrictInfo($cityId);

        $distictCodeList = array_keys($disticts['districts']);
        if($type == self::WEEK){
            $lastTime = $this->getLastWeek();
            $prelastTime = $this->getLastWeek(2);
        }else{
            $lastTime = $this->getLastMonth();
            $prelastTime = $this->getLastMonth(2);
        }
        $dateList = self::getDateFromRange($lastTime['start_time'],$lastTime['end_time']);
        $predateList = self::getDateFromRange($prelastTime['start_time'],$prelastTime['end_time']);
//        if($type == self::WEEK){
//
//            $lastdata = $this->period_model->getDistrictWeekData();
//        }else{
//            $lastdata = $this->period_model->getDistrictMonthData();
//        }

        if($timeType == self::ALLDAY){
            if($type == self::WEEK){
                $lastData = $this->period_model->getDistrictWeekData($cityId,$distictCodeList,$lastTime['start_time']);
                $prelastData = $this->period_model->getDistrictWeekData($cityId,$distictCodeList,$prelastTime['start_time']);
            }else{
                $lastData = $this->period_model->getDistrictMonthData($cityId,$distictCodeList,explode('-',$lastTime['start_time'])[0],explode('-',$lastTime['start_time'])[1]);
                $prelastData = $this->period_model->getDistrictMonthData($cityId,$distictCodeList,explode('-',$prelastTime['start_time'])[0],explode('-',$prelastTime['start_time'])[1]);
            }
        }elseif($timeType == self::MORNING){
            $hour = self::getMorningHours();
            $lastData = $this->period_model->getDistrictHourData($cityId,$distictCodeList,$dateList,$hour);
        }else{
            $hour = self::getNightHours();
            $lastData = $this->period_model->getDistrictHourData($cityId,$distictCodeList,$dateList,$hour);
        }


        $lastDataAve = [];
        $prelastDataAve = [];
        foreach ($lastData as $k => $v){
            if(!isset($lastDataAve[$v['district_id']])){
                $lastDataAve[$v['district_id']] = [
                    'stop_delay'=>0,
                    'speed'=>0,
                    'traj_count'=>0
                ];
            }
            $lastDataAve[$v['district_id']] = [
                'district_id'=>$v['district_id'],
                'stop_delay'=>$lastDataAve[$v['district_id']]['stop_delay']+$v['stop_delay']*$v['traj_count'],
                'speed'=>$lastDataAve[$v['district_id']]['speed']+$v['speed']*$v['traj_count'],
                'traj_count'=>$lastDataAve[$v['district_id']]['traj_count']+$v['traj_count']
            ];
        }
        //计算列表
        $final_data = [];
        $maxDelay = 0;//本周平均延误最高
        $maxDelayId = null;
        foreach ($lastDataAve as $lv){
            $aveStopDelay = round($lv['stop_delay']/$lv['traj_count'],2);
            $final_data[$lv['district_id']] = [
                'district_id'=>$lv['district_id'],
                'district_name'=>$disticts['districts'][$lv['district_id']],
                'stop_delay'=>$aveStopDelay,
                'speed'=>round(($lv['speed']/$lv['traj_count'])*3.6,2),
            ];
            if($aveStopDelay>$maxDelay){
                $maxDelay = $aveStopDelay;
                $maxDelayId = $lv['district_id'];
            }
        }

        //计算与上周的对比
        $preMaxDelay = -99999;
        $preMaxDelayId = null;
        $preMinDelay = 99999;
        $preMinDelayId = null;
        if($timeType == self::ALLDAY){
            foreach ($prelastData as $k => $v){
                $prelastDataAve[$v['district_id']] = [
                    'district_id'=>$v['district_id'],
                    'stop_delay'=>$v['stop_delay'],
                    'speed'=>$v['speed']
                ];
                $MoM  = ($final_data[$v['district_id']]['stop_delay'] - $v['stop_delay'])/$v['stop_delay'];
                if($MoM > $preMaxDelay){
                    $preMaxDelayId = $v['district_id'];
                    $preMaxDelay = $MoM;
                }
                if($MoM < $preMinDelay){
                    $preMinDelayId = $v['district_id'];
                    $preMinDelay = $MoM;
                }

            }
        }

        if($timeType == self::ALLDAY && $type ==self::WEEK ){
            $summary = "其中".$disticts['districts'][$maxDelayId]."在本周最为拥堵。"."环比上周";
            if($preMaxDelay>0){
                $summary.=$disticts['districts'][$preMaxDelayId]."改善情况最好。";
            }
            if($preMinDelay<0){
                $summary.=$disticts['districts'][$preMinDelayId]."恶化情况最严重。";
            }
        }elseif ($timeType == self::ALLDAY && $type ==self::MONTH ){
            $summary = "其中".$disticts['districts'][$maxDelayId]."在本月最为拥堵。"."环比上月";
            if($preMaxDelay>0){
                $summary.=$disticts['districts'][$preMaxDelayId]."改善情况最好。";
            }
            if($preMinDelay<0){
                $summary.=$disticts['districts'][$preMinDelayId]."恶化情况最严重。";
            }
        }else{
            $summary = "";
        }

        return $this->response(array(
            'start_time'=>$lastTime['start_time'],
            'end_time'=>$lastTime['end_time'],
            'districtList'=>array_values($final_data),
            'summary'=>$summary
        ));
    }

    /**
     *周、月报–延误最大top20(top10)
     */
    public function quotaTopJunction()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'city_id'     => 'nullunable',
                'type'      => 'nullunable',
                'time_type'      => 'nullunable',
                'top_num'      => 'nullunable',
                'quota_key' => 'nullunable'
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
        $topNum = $params['top_num'];
        $quotaKey = $params['quota_key'];
        $quotaInfo = array(
            'queue_length'=>array(
                'name' => '平均排队长度',
                'round' => 0
            ),
            'stop_delay'=>array(
                'name' => '平均延误时间',
                'round' => 2
            )
        );
        if(!isset($quotaInfo[$quotaKey])){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = "非法quota_key";
            return;
        }
        $needNameJunctions = [];

        if($type == self::WEEK){
            $lastTime = $this->getLastWeek();
            $preLastTime = $this->getLastWeek(2);
        }else{
            $lastTime = $this->getLastMonth();
            $preLastTime = $this->getLastMonth(2);
        }
        $dateList = self::getDateFromRange($lastTime['start_time'],$lastTime['end_time']);
        $preDateList = self::getDateFromRange($preLastTime['start_time'],$preLastTime['end_time']);
        if($timeType == self::ALLDAY){
            $hour = null;
        }elseif ($timeType == self::MORNING){
            $hour = self::getMorningHours();
        }else{
            $hour = self::getNightHours();
        }

        if($timeType == self::ALLDAY && $type == self::WEEK){
            $data = $this->period_model->getJunctionWeekData($cityId,null,$lastTime['start_time'],$quotaKey.' desc');
            $predata = $this->period_model->getJunctionWeekData($cityId,null,$preLastTime['start_time'],$quotaKey.' desc');
        }elseif($timeType == self::ALLDAY && $type == self::MONTH){
            $data = $this->period_model->getJunctionMonthData($cityId,null,explode('-',$lastTime['start_time'])[0],explode('-',$lastTime['start_time'])[1],$quotaKey.' desc');
            $predata = $this->period_model->getJunctionMonthData($cityId,null,explode('-',$preLastTime['start_time'])[0],explode('-',$preLastTime['start_time'])[1],$quotaKey.' desc');
        }else{
            $data =[];
            $predata = [];
            $datatmp = $this->period_model->getJunctionHourData($cityId,$dateList,$hour,$quotaKey.' desc');

            $predatatmp = $this->period_model->getJunctionHourData($cityId,$preDateList,$hour,$quotaKey.' desc');

            $evaluate = new EvaluateQuota();

            if($quotaKey == 'stop_delay'){
                $dayWorst = $evaluate->getJunctionStopDelayAve($datatmp);
                $datatmp = $evaluate->getJunctionStopDelayAveTable($datatmp);
                $predatatmp = $evaluate->getJunctionStopDelayAveTable($predatatmp);
            }else{
                $dayWorst = $evaluate->getJunctionQueueLengthAve($datatmp);
                $datatmp = $evaluate->getJunctionQueueLengthAveTable($datatmp);
                $predatatmp = $evaluate->getJunctionQueueLengthAveTable($predatatmp);
            }

            usort($datatmp, array("PeriodReport","quotasort"));
            usort($predatatmp, array("PeriodReport","quotasort"));
            foreach ($datatmp as $dtk => $dtv){
                $data[] = array(
                    'logic_junction_id'=>$dtv[0],
                    $quotaKey => $dtv[1]
                );
            }
            foreach ($predatatmp as $pdtk => $pdtv){
                $predata[] = array(
                    'logic_junction_id'=>$pdtv[0],
                    $quotaKey => $pdtv[1]
                );
            }
        }

        $finalData = array();

        $preRank=[];//上周排名
        foreach ($predata as $pk =>$pv){
            $preRank[$pv['logic_junction_id']]['rank'] = $pk+1;
            $preRank[$pv['logic_junction_id']]['data'] = $pv;
        }
        $maxMoM = -999;
        $maxMoMJunction = array();
        foreach ($data as $k => $v){
            $item = array(
                'rank'=>$k+1,
                'logic_junction_id' => $v['logic_junction_id'],
                $quotaKey => round($v[$quotaKey],$quotaInfo[$quotaKey]['round']),
                'last_rank' => isset($preRank[$v['logic_junction_id']]) ? $preRank[$v['logic_junction_id']]['rank'] : "--",
                'MoM' => $v[$quotaKey] - (isset($preRank[$v['logic_junction_id']]) ? $preRank[$v['logic_junction_id']]['data'][$quotaKey] : $v[$quotaKey]),
            );

            if($item['MoM'] > $maxMoM){
                $maxMoMJunction['logic_junction_id'] = $v['logic_junction_id'];
                $maxMoMJunction['rank'] = $item['rank'];
                $maxMoMJunction['last_rank'] = $item['last_rank'];
                $maxMoMJunction['d'] = $item['MoM'];
                $maxMoM = $item['MoM'];
            }
            $finalData['junction_list'][] = $item;

        }
        $needNameJunctions = array_column(array_slice($finalData['junction_list'],0,$topNum),'logic_junction_id');
        $needNameJunctions[] = $maxMoMJunction['logic_junction_id'];

        $junctionInfos = $this->waymap_model->getJunctionInfo(implode(",",$needNameJunctions),['key'=>'logic_junction_id','value'=>['name']]);


        $summary = "其中".$junctionInfos[$finalData['junction_list'][0]['logic_junction_id']]['name'];
        if($type  == self::WEEK){
            $period = "周";
        }else{
            $period = "月";
        }
        $timePeriod = "";
        if($timeType == self::ALLDAY){
            $dayWorstQuota = $this->period_model->getJunctionDayData($cityId,$finalData['junction_list'][0]['logic_junction_id'],$dateList,$quotaKey.' desc');
        }elseif ($timeType == self::MORNING){
            $timePeriod = "早高峰";
            $dayWorstQuota = [];
            $dayWorstQuota[] = [
                'date'=>$dayWorst[$finalData['junction_list'][0]['logic_junction_id']][0][0],
                $quotaKey => $dayWorst[$finalData['junction_list'][0]['logic_junction_id']][0][1]
            ];
        }else{
            $timePeriod = "晚高峰";
            $dayWorstQuota = [];
            $dayWorstQuota[] = [
                'date'=>$dayWorst[$finalData['junction_list'][0]['logic_junction_id']][0][0],
                $quotaKey => $dayWorst[$finalData['junction_list'][0]['logic_junction_id']][0][1]
            ];

        }

        $summary .= "本".$period.$timePeriod.$quotaInfo[$quotaKey]['name']."最大。";
        $summary .= "在".$dayWorstQuota[0]['date'].$quotaInfo[$quotaKey]['name']."最大,达到".round($dayWorstQuota[0][$quotaKey],2)."。";

        if($maxMoMJunction['d']>0){
            $summary .= "环比上".$period.$junctionInfos[$maxMoMJunction['logic_junction_id']]['name']."恶化情况最严重,由上个".$period.$maxMoMJunction['last_rank']."名,变化至本".$period.$maxMoMJunction['rank']."名,";
            $summary .= "下个".$period."需要重点关注延误变大原因";
        }

        $finalData['summary'] = $summary;
        $finalData['junction_list'] = array_slice($finalData['junction_list'],0,$topNum);
        $finalData['quota_name']=$quotaInfo[$quotaKey]['name'];
        if($timeType == self::ALLDAY){
            $finalData['quota_desc']="本".$period.$quotaInfo[$quotaKey]['name']."最大的".$topNum."个路口展示";
        }else{
            $finalData['quota_desc']="延误top".$topNum.",排队长度top".$topNum."路口数据与上".$period."排名进行对比,并分析趋势";
        }

        if($timeType == self::MORNING){
            $finalData['quota_title']="工作日早高峰分析(06:30 ~ 09:30)";
        }elseif ($timeType == self::NIGHT){
            $finalData['quota_title']="工作日晚高峰分析(16:30 ~ 19:30)";
        }else{
            $finalData['quota_title']=$quotaInfo[$quotaKey]['name']."top".$topNum."路口分析";
        }

        //补齐路口名称
        foreach ($finalData['junction_list'] as $fjk => $fjv){
            $finalData['junction_list'][$fjk]['junction_name'] = $junctionInfos[$fjv['logic_junction_id']]['name'];
        }

        return $this->response($finalData);
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
            $preiod = "周";
            $lastTime = $this->getLastWeek();
            $prelastTime = $this->getLastWeek(2);
        }else{
            $preiod = "月";
            $lastTime = $this->getLastMonth();
            $prelastTime = $this->getLastMonth(2);
        }

        if($timeType == self::MORNING){
            $schedule = "早高峰";
            $hour = array('06:30','07:00','07:30','08:00','08:30','09:00','09:30');
        }else{
            $schedule = "晚高峰";
            $hour = array('16:30','17:00','17:30','18:00','18:30','19:00','19:30');
        }
        $dateList = self::getDateFromRange($lastTime['start_time'],$lastTime['end_time']);
        $preDataList = self::getDateFromRange($prelastTime['start_time'],$prelastTime['end_time']);
        $data = $this->period_model->getCityHourData($cityId,$dateList,$hour);
        $preData = $this->period_model->getCityHourData($cityId,$preDataList,$hour);
        $evaluate = new EvaluateQuota();
        $lastcharData = $evaluate->getCityStopDelayAve($data);
        $preLastcharData = $evaluate->getCityStopDelayAve($preData);

        $sum = 0;
        $count = 0;

        foreach ($lastcharData['total'] as $k => $v){
            $sum += $v[1];
            $count += 1;
        }
        $aveStopDelay = $sum/$count;
        $sum = 0;
        $count = 0;
        foreach ($preLastcharData['total'] as $k => $v){
            $sum += $v[1];
            $count += 1;
        }
        $preaveStopDelay = $sum/$count;
        $stopDelayMoM = (($aveStopDelay - $preaveStopDelay)/$preaveStopDelay) * 100;
        $change = $stopDelayMoM > 0 ? "增加":"减少";
        $summary = "本".$preiod.$schedule."平均延误为".round($aveStopDelay)."秒,环比上周".$change.abs(round($stopDelayMoM,2))."%。";
        return $this->response(array(
            'base'=>array(
                'period'=>$lastcharData['total'],
                'last_period'=>$preLastcharData['total']
            ),
            'summary'=>$summary
        ));

    }

    /**
     *周、月报–早(晚)高峰平均速度柱状图
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
            $preiod = "周";
            $lastTime = $this->getLastWeek();
            $prelastTime = $this->getLastWeek(2);
        }else{
            $preiod = "月";
            $lastTime = $this->getLastMonth();
            $prelastTime = $this->getLastMonth(2);
        }

        if($timeType == self::MORNING){
            $schedule = "早高峰";
            $hour = array('06:30','07:00','07:30','08:00','08:30','09:00','09:30');
        }else{
            $schedule = "晚高峰";
            $hour = array('16:30','17:00','17:30','18:00','18:30','19:00','19:30');
        }
        $dateList = self::getDateFromRange($lastTime['start_time'],$lastTime['end_time']);
        $preDataList = self::getDateFromRange($prelastTime['start_time'],$prelastTime['end_time']);
        $data = $this->period_model->getCityHourData($cityId,$dateList,$hour);
        $preData = $this->period_model->getCityHourData($cityId,$preDataList,$hour);

        //速度单位转换
        foreach ($data as $dk => $dv){
            $data[$dk]['speed'] = $dv['speed']*3.6;
        }
        foreach ($preData as $pdk => $pdv){
            $preData[$pdk]['speed'] = $pdv['speed']*3.6;
        }

        $evaluate = new EvaluateQuota();
        $lastcharData = $evaluate->getCitySpeedAve($data);
        $preLastcharData = $evaluate->getCitySpeedAve($preData);

        $sum = 0;
        $count = 0;

        foreach ($lastcharData['total'] as $k => $v){
            $sum += $v[1];
            $count += 1;
        }
        $aveStopDelay = $sum/$count;
        $sum = 0;
        $count = 0;
        foreach ($preLastcharData['total'] as $k => $v){
            $sum += $v[1];
            $count += 1;
        }
        $preaveStopDelay = $sum/$count;
        $stopDelayMoM = (($aveStopDelay - $preaveStopDelay)/$preaveStopDelay) * 100;
        $change = $stopDelayMoM > 0 ? "增加":"减少";
        $summary = "本".$preiod.$schedule."平均运行速度为".round($aveStopDelay,2)."km/h,环比上周".$change.abs(round($stopDelayMoM,2))."%。";
        return $this->response(array(
            'base'=>array(
                'period'=>$lastcharData['total'],
                'last_period'=>$preLastcharData['total']
            ),
            'summary'=>$summary
        ));
    }

    private function getLastWeek($t = 1)
    {

        if(intval(date('w')) == 1){
            $mon = $t*-1;
        }else{
            $mon = ($t+1)*-1;
        }

        return array(
            'start_time'=>date('Y-m-d', strtotime($mon.' monday', time())),
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