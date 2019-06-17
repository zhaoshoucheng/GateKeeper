<?php
/**
 * Created by PhpStorm.
 * User: niuyufu
 * Date: 19/5/23
 * Time: 下午5:27
 */

namespace Services;

use Services\DiagnosisNoTimingService;

/**
 * Class TimeframescatterService
 * @package Services
 * @property \Scatter_model $scatter_model
 * @property \Timing_model $timing_model
 */
class TimeframescatterService extends BaseService
{
    protected $dianosisService;

    public function __construct()
    {
        parent::__construct();

        $this->dianosisService = new DiagnosisNoTimingService();
        $this->load->model('scatter_model');
        $this->load->model('timing_model');
    }

    public function getTrackDataNoTaskId($data)
    {
        // todo 这里调用新版配时任务
        // 获取配时信息 周期 相位差 绿灯开始结束时间 所有相位最大周期
        $timingData = [
            'junction_id' => $data['junction_id'],
            'dates'       => $data['dates'],
            'time_range'  => $data['time_range'],
            'flow_id'     => trim($data['flow_id']),
            'timingType'  => $data['timingType']
        ];
        $timing = $this->timing_model->gitFlowTimingByOptimizeScatter($timingData);
        if (!$timing) {
            return [];
        }

        //请求散点图数据
        $dparam = [
            'city_id' => $data['city_id'],
            'flow_id' => $data['flow_id'],
            'time_range' => $data['time_range'],
            'junction_id' => $data['junction_id'],
            'dates' => $data['dates'],
        ];
//        print_r($dparam);exit;
        $result_data = $this->dianosisService->getScatterDiagram($dparam);
        $scatterList = [];
        foreach ($result_data as $item){
            $scatterList = array_merge($scatterList,$item['dataList']);
        }
        if(count($scatterList) ==0){
            return [];
        }
        $resultList = [];
        $timeArr = explode("-",$data['time_range']);
        $scatterValues = array_column($scatterList, 1);
        $maxD = max($scatterValues);
        $minD = min($scatterValues);
        $info = [
            "comment"=>$timing["info"]["comment"],
            "id"=>$timing["info"]["logic_flow_id"],
            "x"=>[
                "min"=>$timeArr[0].":00",
                "max"=>$timeArr[1].":00",
            ],
            "y"=>[
                "min"=>$minD,
                "max"=>$maxD,
            ],
        ];
        array_multisort(array_column($scatterList, 0), SORT_ASC, $scatterList);
        foreach ($scatterList as $key=>$item){
            $key = date("H:i:s",strtotime(date("Y-m-d"))+$item[0]);
            $resultList[] = [$key,$item[1]];
        }
        $result = [
            "info"=>$info,
            "dataList"=>$resultList,
            "planList"=>$timing["planList"],
        ];
        return $result;
    }

}