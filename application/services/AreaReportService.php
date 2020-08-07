<?php
/**
 * 区域分析报告模块业务逻辑
 */

namespace Services;

use Services\AreaService;
use Services\ReportService;
use Services\DataService;
use Services\RoadReportService;

class AreaReportService extends BaseService{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('report_conf');

        $this->load->model('openCity_model');
        $this->load->model('waymap_model');
        $this->load->model('area_model');
        $this->load->model('pi_model');
        $this->load->model('traj_model');
        $this->load->model('thermograph_model');

        $this->areaService = new AreaService();
        $this->reportService = new ReportService();
        $this->dataService = new DataService();
        $this->roadReportService = new RoadReportService();
    }

    //获取两个阶段的pi数据
    public function getJuncsPiCompare($cityID,$juncs,$startTime,$endTime){

        $theDatelist = $this->getDateFromRange($startTime,$endTime);
        $pi = $this->pi_model->getGroupJuncAvgPiWithDates($cityID,$juncs ,$theDatelist,$this->createHours());

        //上阶段pi
        $laststage = $this->getLastStage($startTime,$endTime);
        $lastDatelist = $this->getDateFromRange($laststage[0],$laststage[1]);
        $lastpi = $this->pi_model->getGroupJuncAvgPiWithDates($cityID,$juncs ,$lastDatelist,$this->createHours());

        return [
            'pi'=>$pi,
            'last_pi'=>$lastpi,
        ];

    }

    public function getLastStage($startTime,$endTime){

        $len = $this->getDateFromRange($startTime,$endTime);
        $nstart= strtotime($startTime) - 3600*24*count($len);
        $nend=strtotime($endTime) - 3600*24*count($len);

        return [date("Y-m-d",$nstart),date("Y-m-d",$nend)];

    }

    //南京定制版本
    public function introductionNJ($params){
        $city_id = $params['city_id'];
        $area_id = $params['area_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $datestr =  date('Y年m月d日', strtotime($start_date))."~".date('Y年m月d日', strtotime($end_date));
        if($start_date == $end_date){
            $datestr =  date('Y年m月d日', strtotime($start_date));
        }

        if($params['date_type'] == 1){
            $datestr.="(工作日)";
        }elseif ($params['date_type']==2){
            $datestr.="(周末)";
        }

//        本次报告区域为XX市，分析区域包含XX区、XX区等行政区域，共XXX个路口。本次报告根据20XX年XX月XX日～XX月XX日数据该区域进行分析，整体延误指数为30.55s，与前一周相比无变化／上升12%／下降12%，基本持平/更加严重/得到缓解。
        $tpl = "本次报告区域为%s市%s，共%s个路口。本次报告根据%s数据对该区域进行分析，整体交叉口延误指数为%s，与%s相比%s，%s";


        $city_info = $this->openCity_model->getCityInfo($city_id);


//        $area_info = $this->area_model->getAreaInfo($area_id);


        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $city_id,
            'area_id' => $area_id,
        ]);
        $logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));
        $juncLen = count(explode(",",$logic_junction_ids));
        $junctions_info = $this->waymap_model->getJunctionInfo($logic_junction_ids);


        $theDatelist = $this->getDateFromRange($start_date,$end_date);
        $theDatelist = $this->reportService->skipDate($theDatelist,$params['date_type']);

        if(count($theDatelist)==1){
            $stageType="前一日";
        }else if(count($theDatelist)==7){
            $stageType="前一周";
        }else if(count($theDatelist)<40){
            $stageType="前一月";
        }else{
            $stageType="前一季";
        }

        //本阶段pi

        $pi = $this->pi_model->getGroupJuncAvgPiWithDates($city_id,explode(",",$logic_junction_ids) ,$theDatelist,$this->createHours());

        //上阶段pi
        $laststage = $this->getLastStage($start_date,$end_date);
        $lastDatelist = $this->getDateFromRange($laststage[0],$laststage[1]);
        $lastpi = $this->pi_model->getGroupJuncAvgPiWithDates($city_id,explode(",",$logic_junction_ids) ,$lastDatelist,$this->createHours());
        if($lastpi > 0 ){
            $mon = round(($pi-$lastpi)*100/$lastpi,2);
        }else{
            $mon = 100;
        }
        if($mon>=-10 && $mon<=10){
            $conclusion="基本持平";
        }else if($mon<-10){
            $conclusion="得到缓解";
        }else{
            $conclusion="更加严重";
        }
        if($mon == 0){
            $mon="无变化";
        }elseif ($mon >0){
            $mon = "上升".$mon."%";
        }else{
            $mon = "下降".($mon*(-1))."%";
        }

        $districts_name = implode('、', array_unique(array_column($junctions_info, 'district_name')));

        $desc = sprintf($tpl, $city_info['city_name'], $districts_name,$juncLen,$datestr,round($pi,2)."s",$stageType,$mon,$conclusion);

        return [
            'desc' => $desc,
            'area_info' => $area_detail,
        ];
    }

    //济南定制化需求
    public function introductionJN($params){

        $tpl = "本次报告区域为%s。本次报告根据%s数据对该区域进行分析，整体PI为%s，%sPI为%s，与%s相比%s，%s";

        $city_id = $params['city_id'];
        $area_id = $params['area_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $datestr =  date('Y年m月d日', strtotime($start_date))."~".date('Y年m月d日', strtotime($end_date));
        if($start_date == $end_date){
            $datestr =  date('Y年m月d日', strtotime($start_date));
        }

        $city_info = $this->openCity_model->getCityInfo($city_id);
//        $area_info = $this->area_model->getAreaInfo($area_id);
        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $city_id,
            'area_id' => $area_id,
        ]);
        $logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));
//        $junctions_info = $this->waymap_model->getJunctionInfo($logic_junction_ids);
//        $districts_name = implode('、', array_unique(array_column($junctions_info, 'district_name')));
        $districts_name = $area_detail['area_name'];


        $theDatelist = $this->getDateFromRange($start_date,$end_date);
        if(count($theDatelist)==1){
            $stageType="前一日";
        }else if(count($theDatelist)==7){
            $stageType="前一周";
        }else if(count($theDatelist)<40){
            $stageType="前一月";
        }else{
            $stageType="前一季";
        }

        $piInfo = $this->getJuncsPiCompare($city_id,explode(",",$logic_junction_ids),$start_date,$end_date);

        if($piInfo['last_pi'] > 0 ){
            $mon = round(($piInfo['pi']-$piInfo['last_pi'])*100/$piInfo['last_pi'],2);
        }else{
            $mon = 100;
        }
        if($mon>=-10 && $mon<=10){
            $conclusion="基本持平";
        }else if($mon<-10){
            $conclusion="得到缓解";
        }else{
            $conclusion="更加严重";
        }
        if($mon == 0){
            $mon="无变化";
        }elseif ($mon >0){
            $mon = "上升".$mon."%";
        }else{
            $mon = "下降".($mon*(-1))."%";
        }


        $desc = sprintf($tpl,  $districts_name, $datestr,round($piInfo['pi'],2),$stageType,round($piInfo['last_pi'],2),$stageType,$mon,$conclusion);


        return [
            'desc' => $desc,
            'area_info' => $area_detail,
        ];
    }

    public function introduction($params) {

        $city_id = $params['city_id'];
        $area_id = $params['area_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $datestr =  date('Y年m月d日', strtotime($start_date))."~".date('Y年m月d日', strtotime($end_date));
        if($start_date == $end_date){
            $datestr =  date('Y年m月d日', strtotime($start_date));
        }

        if($params['date_type'] == 1){
            $datestr.="(工作日)";
        }elseif ($params['date_type']==2){
            $datestr.="(周末)";
        }
        $tpl = "本次报告区域为%s市，分析区域包含%s等行政区域。本次报告根据%s数据对该区域进行分析。";


        $city_info = $this->openCity_model->getCityInfo($city_id);
        if (empty($city_info)) {

        }

        $area_info = $this->area_model->getAreaInfo($area_id);
        if (empty($area_info)) {

        }

        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $city_id,
            'area_id' => $area_id,
        ]);
        $logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

        $junctions_info = $this->waymap_model->getJunctionInfo($logic_junction_ids);
        if (empty($junctions_info)) {

    	}
        if( isset($params['userapp']) && $params['userapp'] == 'jinanits'){
            $dates = $this->getDateFromRange($start_date,$end_date);
            $pi = $this->pi_model->getGroupJuncAvgPiWithDates($city_id,explode(",",$logic_junction_ids) ,$dates,$this->createHours());
            $tpl = "本次报告分析区域为%s市，整体PI为".round($pi,2)."，分析区域包含%s等行政区域。本次报告根据%s数据对该区域进行分析。";

        }
        $districts_name = implode('、', array_unique(array_column($junctions_info, 'district_name')));

        $desc = sprintf($tpl, $city_info['city_name'], $districts_name,$datestr);

        return [
            'desc' => $desc,
            'area_info' => $area_detail,
        ];
    }

    public function queryAreaDataComparison($params) {
    	$tpl = "上图展示了分析区域%s与%s路口平均延误的对比，%s该区域拥堵程度与%s相比%s。";

        $city_id = intval($params['city_id']);
        $area_id = $params['area_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];



        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $city_id,
            'area_id' => $area_id,
        ]);
        $logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

        $report_type = $this->reportService->report_type($start_date, $end_date);
        $last_report_date = $this->reportService->last_report_date($start_date, $end_date, $report_type);
        $last_start_date = $last_report_date['start_date'];
        $last_end_date = $last_report_date['end_date'];



        $theDatelist = $this->reportService->getDatesFromRange($start_date,$end_date);
        $theDatelist = $this->reportService->skipDate($theDatelist,$params['date_type']);

        $lastDatelist = $this->reportService->getDatesFromRange($last_start_date,$last_end_date);
        $lastDatelist = $this->reportService->skipDate($lastDatelist,$params['date_type']);


        $now_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $theDatelist,
    		'logic_junction_ids' => !empty($logic_junction_ids) ? explode(',', $logic_junction_ids): [],
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(traj_count) as traj_count",
            "group_by" => "hour",
    	], "POST", 'json');
    	$last_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $lastDatelist,
    		'logic_junction_ids' => !empty($logic_junction_ids) ? explode(',', $logic_junction_ids): [],
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(traj_count) as traj_count",
            "group_by" => "hour",
        ], "POST", 'json');

        $now_data = array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $now_data[2]);
        usort($now_data, function($a, $b) {
            return ($a['x'] < $b['x']) ? -1 : 1;
        });
        $last_data = array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $last_data[2]);
        usort($last_data, function($a, $b) {
            return ($a['x'] < $b['x']) ? -1 : 1;
        });

        $text = $this->reportService->getComparisonText(array_column($now_data, 'y'), array_column($last_data, 'y'), $report_type);

        $desc = sprintf($tpl, $text[1], $text[2], $text[1], $text[2], $text[0]);

        return [
            'info' => [
                'desc' => $desc,
            ],
            'chart' => [
                'title' => '平均延误对比',
                'scale_title' => '平均延误(s)',
                'series' => [
                    [
                        'name' => $text[1],
                        'data' => $this->reportService->addto48($now_data),
                    ],
                    [
                        'name' => $text[2],
                        'data' => $this->reportService->addto48($last_data),
                    ],
                ],
            ],
        ];
    }

    public function queryAreaDataComparisonNJ($params) {
        $tpl = "上图展示了研究区域总体运行状态（交叉口延误指数）%s与%s的对比，%s该区域拥堵程度与%s相比%s。";

        $city_id = intval($params['city_id']);
        $area_id = $params['area_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];



        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $city_id,
            'area_id' => $area_id,
        ]);
        $logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

        $report_type = $this->reportService->report_type($start_date, $end_date);
        $last_report_date = $this->reportService->last_report_date($start_date, $end_date, $report_type);
        $last_start_date = $last_report_date['start_date'];
        $last_end_date = $last_report_date['end_date'];

        $theDatelist = $this->reportService->getDatesFromRange($start_date, $end_date);
        $theDatelist = $this->reportService->skipDate($theDatelist,$params['date_type']);
        $now_data = $this->pi_model->getJunctionsPiByHours($city_id, explode(',', $logic_junction_ids), $theDatelist);
        usort($now_data, function($a, $b) {
            return ($a['hour'] < $b['hour']) ? -1 : 1;
        });
        $lastDatelist = $this->reportService->getDatesFromRange($last_start_date, $last_end_date);
        $lastDatelist = $this->reportService->skipDate($lastDatelist,$params['date_type']);

        $last_data = $this->pi_model->getJunctionsPiByHours($city_id, explode(',', $logic_junction_ids), $lastDatelist);
        usort($last_data, function($a, $b) {
            return ($a['hour'] < $b['hour']) ? -1 : 1;
        });

        $text = $this->reportService->getComparisonText(array_column($now_data, 'y'), array_column($last_data, 'y'), $report_type, 'pi');

        $desc = sprintf($tpl, $text[1], $text[2], $text[1], $text[2], $text[0]);

        return [
            'info' => [
                'desc' => $desc,
            ],
            'chart' => [
                'title' => '交叉口延误指数',
                'scale_title' => '',
                'series' => [
                    [
                        'name' => $text[1],
                        'data' => $this->reportService->addto48(array_map(function($item) {
                            return [
                                'x' => $item['hour'],
                                'y' => round($item['pi'], 2),
                            ];
                        }, $now_data)),
                    ],
                    [
                        'name' => $text[2],
                        'data' => $this->reportService->addto48(array_map(function($item) {
                            return [
                                'x' => $item['hour'],
                                'y' => round($item['pi'], 2),
                            ];
                        }, $last_data)),
                    ],
                ],
            ],
        ];
    }

    public function queryAreaQuotaDataNJ($params) {
        $tpl = "下图利用滴滴数据绘制了该区域全天24小时各项运行指标（停车次数、停车延误、行驶速度）。通过数据分析，该区域的早高峰约为%s-%s，晚高峰约为%s-%s。与平峰相比，早晚高峰的停车次数达到%.2f次/车/路口，停车延误接近%.2f秒/车/路口，行驶速度也达到%.2f千米/小时左右。与%s相比，%s停车次数%s，停车延误%s，行驶速度%s。";
        $conclusion="通过交通大数据分析，该干线的早高峰时段为%s-%s，晚高峰时段为%s-%s。早晚高峰的运行情况与平峰相比，停车次数达到%.2f次/车/路口，停车延误接近%.2f秒/车/路口，行驶速度也达到%.2f千米/小时左右，需重点关注存在问题的路口，可以通过调整路口的绿信比、相位差和周期的方式进行优化，从而缓解交通压力。";

        $city_id = intval($params['city_id']);
        $area_id = $params['area_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];


        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $city_id,
            'area_id' => $area_id,
        ]);
        $logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

        $report_type = $this->reportService->report_type($start_date, $end_date);
        $last_report_date = $this->reportService->last_report_date($start_date, $end_date, $report_type);
        $last_start_date = $last_report_date['start_date'];
        $last_end_date = $last_report_date['end_date'];

        $theDatelist = $this->reportService->getDatesFromRange($start_date, $end_date);
        $theDatelist = $this->reportService->skipDate($theDatelist,$params['date_type']);

        $lastDatelist = $this->reportService->getDatesFromRange($last_start_date, $last_end_date);
        $lastDatelist = $this->reportService->skipDate($lastDatelist,$params['date_type']);

        $morning_peek = $this->reportService->getMorningPeekRange($city_id, explode(',', $logic_junction_ids), $theDatelist);
        $morning_peek_hours = $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']);

        $evening_peek = $this->reportService->getEveningPeekRange($city_id, explode(',', $logic_junction_ids), $theDatelist);
        $evening_peek_hours = $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']);
        $peek_hours = array_merge($morning_peek_hours, $evening_peek_hours);

        $now_data = $this->dataService->call("/report/GetIndex", [
            'city_id' => $city_id,
            'dates' => $theDatelist,
            'logic_junction_ids' => explode(',', $logic_junction_ids),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(stop_time_cycle * traj_count) AS stop_time_cycle, sum(speed * traj_count) AS speed, sum(traj_count) as traj_count",
            "group_by" => "hour",
        ], "POST", 'json');
        $now_data = $now_data[2];
        usort($now_data, function($a, $b) {
            return ($a['key'] < $b['key']) ? -1 : 1;
        });

        $last_data = $this->dataService->call("/report/GetIndex", [
            'city_id' => $city_id,
            'dates' => $lastDatelist,
            'logic_junction_ids' => explode(',', $logic_junction_ids),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(stop_time_cycle * traj_count) AS stop_time_cycle, sum(speed * traj_count) AS speed, sum(traj_count) as traj_count",
            "group_by" => "hour",
        ], "POST", 'json');
        $last_data = $last_data[2];
        usort($last_data, function($a, $b) {
            return ($a['key'] < $b['key']) ? -1 : 1;
        });

        $now_stop_time_cycle_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $now_data));
        $last_stop_time_cycle_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $last_data));
        $stop_time_cycle_text = $this->reportService->getComparisonText(array_column($now_stop_time_cycle_data, 'y'), array_column($last_stop_time_cycle_data, 'y'), $report_type, 'stop_time_cycle');
        $now_stop_delay_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_time_cycle']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $now_data));
        $last_stop_delay_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_time_cycle']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $last_data));
        $stop_delay_text = $this->reportService->getComparisonText(array_column($now_stop_delay_data, 'y'), array_column($last_stop_delay_data, 'y'), $report_type, 'stop_delay');
        $now_speed_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['speed']['value'] / $item['traj_count']['value'] * 3.6, 2),
            ];
        }, $now_data));
        $last_speed_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['speed']['value'] / $item['traj_count']['value'] * 3.6, 2),
            ];
        }, $last_data));
        $speed_text = $this->reportService->getComparisonText(array_column($now_speed_data, 'y'), array_column($last_speed_data, 'y'), $report_type, 'speed');

        $stop_time_cycle_data = [];
        $stop_delay_data = [];
        $speed_data = [];
        foreach ($now_data as $value) {
            if (! in_array($value['key'], $peek_hours)) {
                continue;
            }
            $stop_time_cycle_data[] = $value['stop_time_cycle']['value'] / $value['traj_count']['value'];
            $stop_delay_data[] = $value['stop_delay']['value'] / $value['traj_count']['value'];
            $speed_data[] = $value['speed']['value'] / $value['traj_count']['value'] * 3.6;
        }
        $stop_time_cycle = 0;
        if (count($stop_time_cycle_data) != 0) {
            $stop_time_cycle = round(array_sum($stop_time_cycle_data) / count($stop_time_cycle_data), 2);
        }
        $stop_delay = 0;
        if (count($stop_delay_data) != 0) {
            $stop_delay = round(array_sum($stop_delay_data) / count($stop_delay_data), 2);
        }
        $speed = 0;
        if (count($speed_data) != 0) {
            $speed = round(array_sum($speed_data) / count($speed_data), 2);
        }

        $desc = sprintf($tpl, $morning_peek['start_hour'], $morning_peek['end_hour'], $evening_peek['start_hour'], $evening_peek['end_hour'], $stop_time_cycle, $stop_delay, $speed, $stop_delay_text[2], $stop_delay_text[1], $stop_time_cycle_text[0], $stop_delay_text[0], $speed_text[0]);

        return [
            'conclusion'=>sprintf($conclusion, $morning_peek['start_hour'], $morning_peek['end_hour'], $evening_peek['start_hour'], $evening_peek['end_hour'], $stop_time_cycle, $stop_delay, $speed),
            'info' => [
                'desc' => $desc,
            ],
            'chart' => [
                [
                    'title' => '停车次数',
                    'scale_title' => '停车次数',
                    'series' => [
                        [
                            'name' => $stop_delay_text[1],
                            'data' => $now_stop_delay_data,
                        ],
                        [
                            'name' => $stop_delay_text[2],
                            'data' => $last_stop_delay_data,
                        ],

                    ],
                ],
                [
                    'title' => '停车延误',
                    'scale_title' => '停车延误(s)',
                    'series' => [
                        [
                            'name' => $stop_time_cycle_text[1],
                            'data' => $now_stop_time_cycle_data,
                        ],
                        [
                            'name' => $stop_time_cycle_text[2],
                            'data' => $last_stop_time_cycle_data,
                        ],
                    ],
                ],
                [
                    'title' => '行驶速度',
                    'scale_title' => '行驶速度(km/h)',
                    'series' => [
                        [
                            'name' => $speed_text[1],
                            'data' => $now_speed_data,
                        ],
                        [
                            'name' => $speed_text[2],
                            'data' => $last_speed_data,
                        ],

                    ],
                ],
            ],
        ];
    }

    public function queryQuotaRank($params) {
        $tpl = "需要注意的是PI指数的计算中考虑了对过饱和、失衡以及溢流状态的惩罚。例如，两个路口在同样的平均停车或延误时间的情况下，如果某个路口出现了过饱和、失衡或者溢流现象，则该路口的PI值会更高。";

        $city_id = intval($params['city_id']);
        $area_id = $params['area_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];



        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $city_id,
            'area_id' => $area_id,
        ]);
        $logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

        $junctions_info = $this->waymap_model->getJunctionInfo($logic_junction_ids);
        if (empty($junctions_info)) {

        }
        $junctions_map = [];
        array_map(function($item) use(&$junctions_map) {
            $junctions_map[$item['logic_junction_id']] = $item;
        }, $junctions_info);

        $report_type = $this->reportService->report_type($start_date, $end_date);
        $last_report_date = $this->reportService->last_report_date($start_date, $end_date, $report_type);
        $last_start_date = $last_report_date['start_date'];
        $last_end_date = $last_report_date['end_date'];

        $theDatelist = $this->reportService->getDatesFromRange($start_date,$end_date);
        $theDatelist = $this->reportService->skipDate($theDatelist,$params['date_type']);

        $lastDatelist = $this->reportService->getDatesFromRange($last_start_date,$last_end_date);
        $lastDatelist = $this->reportService->skipDate($lastDatelist,$params['date_type']);

        $morning_peek = $this->reportService->getMorningPeekRange($city_id, explode(',', $logic_junction_ids), $theDatelist);
        $evening_peek = $this->reportService->getEveningPeekRange($city_id, explode(',', $logic_junction_ids), $theDatelist);
        // var_dump($this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']));
        // var_dump($this->reportService->getHoursFromRange($evening_peek['start_hour'], $morning_peek['end_hour']));

        $morning_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, explode(',', $logic_junction_ids), $theDatelist, $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']));
        usort($morning_pi_data, function($a, $b) {
            return $a['pi'] > $b['pi'] ? -1 : 1;
        });
        $morning_pi_data = array_slice($morning_pi_data, 0, 20);
        $morning_last_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, explode(',', $logic_junction_ids), $lastDatelist, $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']));
        usort($morning_last_pi_data, function($a, $b) {
            return $a['pi'] > $b['pi'] ? -1 : 1;
        });
        $morning_last_pi_data_rank = [];
        for ($i = 0; $i < count($morning_last_pi_data); $i++) {
            $morning_last_pi_data_rank[$morning_last_pi_data[$i]['logic_junction_id']] = $i + 1;
        }
        $morning_data = $this->dataService->call("/report/GetIndex", [
            'city_id' => $city_id,
            'dates' => $theDatelist,
            'logic_junction_ids' => array_column($morning_pi_data, 'logic_junction_id'),
            'hours' => $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(stop_time_cycle * traj_count) AS stop_time_cycle, sum(speed * traj_count) AS speed, sum(traj_count) as traj_count",
            "group_by" => "logic_junction_id",
        ], "POST", 'json');
        $morning_data_map = [];
        array_map(function($item) use(&$morning_data_map) {
            $morning_data_map[$item['key']] = [
                'stop_delay' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
                'stop_time_cycle' => round($item['stop_time_cycle']['value'] / $item['traj_count']['value'], 2),
                'speed' => round($item['speed']['value'] / $item['traj_count']['value'] * 3.6, 2),
            ];
        }, $morning_data[2]);

        $evening_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, explode(',', $logic_junction_ids), $theDatelist, $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']));
        usort($evening_pi_data, function($a, $b) {
            return $a['pi'] > $b['pi'] ? -1 : 1;
        });
        $evening_pi_data = array_slice($evening_pi_data, 0, 20);
        $evening_last_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, explode(',', $logic_junction_ids), $lastDatelist, $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']));
        usort($evening_last_pi_data, function($a, $b) {
            return $a['pi'] > $b['pi'] ? -1 : 1;
        });
        $evening_last_pi_data_rank = [];
        for ($i = 0; $i < count($evening_last_pi_data); $i++) {
            $evening_last_pi_data_rank[$evening_last_pi_data[$i]['logic_junction_id']] = $i + 1;
        }
        $evening_data = $this->dataService->call("/report/GetIndex", [
            'city_id' => $city_id,
            'dates' => $theDatelist,
            'logic_junction_ids' => array_column($evening_pi_data, 'logic_junction_id'),
            'hours' => $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(stop_time_cycle * traj_count) AS stop_time_cycle, sum(speed * traj_count) AS speed, sum(traj_count) as traj_count",
            "group_by" => "logic_junction_id",
        ], "POST", 'json');
        $evening_data_map = [];
        array_map(function($item) use(&$evening_data_map) {
            $evening_data_map[$item['key']] = [
                'stop_delay' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
                'stop_time_cycle' => round($item['stop_time_cycle']['value'] / $item['traj_count']['value'], 2),
                'speed' => round($item['speed']['value'] / $item['traj_count']['value'] * 3.6, 2),
            ];
        }, $evening_data[2]);

        return [
            'morning_peek' => [
                'quota_table_desc' => $tpl,
                'quota_table_data' => array_map(function($item) use($junctions_map, $morning_last_pi_data_rank, $morning_data_map) {
                    return [
                        'logic_junction_id' => $item['logic_junction_id'],
                        'name' => $junctions_map[$item['logic_junction_id']]['name'],
                        'last_rank' => isset($morning_last_pi_data_rank[$item['logic_junction_id']]) ? $morning_last_pi_data_rank[$item['logic_junction_id']] : '-',
                        'stop_delay' => $morning_data_map[$item['logic_junction_id']]['stop_delay'],
                        'stop_time_cycle' => $morning_data_map[$item['logic_junction_id']]['stop_time_cycle'],
                        'speed' => $morning_data_map[$item['logic_junction_id']]['speed'],
                        'PI' => round($item['pi'], 2),
                    ];
                }, $morning_pi_data),
            ],
            'evening_peek' => [
                'quota_table_desc' => $tpl,
                'quota_table_data' => array_map(function($item) use($junctions_map, $evening_last_pi_data_rank, $evening_data_map) {
                    return [
                        'logic_junction_id' => $item['logic_junction_id'],
                        'name' => $junctions_map[$item['logic_junction_id']]['name'],
                        'last_rank' => isset($evening_last_pi_data_rank[$item['logic_junction_id']]) ? $evening_last_pi_data_rank[$item['logic_junction_id']] : '-',
                        'stop_delay' => $evening_data_map[$item['logic_junction_id']]['stop_delay'],
                        'stop_time_cycle' => $evening_data_map[$item['logic_junction_id']]['stop_time_cycle'],
                        'speed' => $evening_data_map[$item['logic_junction_id']]['speed'],
                        'PI' => round($item['pi'], 2),
                    ];
                }, $evening_pi_data),
            ],
        ];
    }

    public function queryAreaCongestion($params) {
        $tpl = "下图展示了分析区域%s高峰延误排名前%d的路口。其中%s%s高峰拥堵情况严重。";

        $city_id = intval($params['city_id']);
        $area_id = $params['area_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];



        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $city_id,
            'area_id' => $area_id,
        ]);
        $logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

        $junctions_info = $this->waymap_model->getJunctionInfo($logic_junction_ids);
        if (empty($junctions_info)) {

        }
        $junctions_map = [];
        array_map(function($item) use(&$junctions_map) {
            $junctions_map[$item['logic_junction_id']] = $item;
        }, $junctions_info);

        $theDatelist = $this->reportService->getDatesFromRange($start_date,$end_date);
        $theDatelist = $this->reportService->skipDate($theDatelist,$params['date_type']);



        $morning_peek = $this->reportService->getMorningPeekRange($city_id, explode(',', $logic_junction_ids), $theDatelist);
        $evenint_peek = $this->reportService->getEveningPeekRange($city_id, explode(',', $logic_junction_ids), $theDatelist);

    	$morning_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $theDatelist,
    		'logic_junction_ids' => !empty($logic_junction_ids)?explode(',', $logic_junction_ids):[],
    		'hours' => $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(traj_count) as traj_count",
            "group_by" => "logic_junction_id",
    	], "POST", 'json');
    	$evening_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $theDatelist,
    		'logic_junction_ids' => !empty($logic_junction_ids)?explode(',', $logic_junction_ids):[],
    		'hours' => $this->reportService->getHoursFromRange($evenint_peek['start_hour'], $evenint_peek['end_hour']),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(traj_count) as traj_count",
            "group_by" => "logic_junction_id",
    	], "POST", 'json');
        // print_r($evening_data);exit;

        $morning_data = array_map(function($item) use($junctions_map) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
                'name' => $junctions_map[$item['key']]['name'],
                'lng' => $junctions_map[$item['key']]['lng'],
                'lat' => $junctions_map[$item['key']]['lat'],
            ];
        }, $morning_data[2]);
        usort($morning_data, function($a, $b) {
            return ($a['y'] > $b['y']) ? -1 : 1;
        });
        $morning_data = array_slice($morning_data, 0, 10);
        $morning_junction_names = array_map(function($item) use($junctions_map) {
            return $item['name'];
        }, array_slice($morning_data, 0, 3));

        $evening_data = array_map(function($item) use($junctions_map) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
                'name' => $junctions_map[$item['key']]['name'],
                'lng' => $junctions_map[$item['key']]['lng'],
                'lat' => $junctions_map[$item['key']]['lat'],
            ];
        }, $evening_data[2]);
        usort($evening_data, function($a, $b) {
            return ($a['y'] > $b['y']) ? -1 : 1;
        });
        $evening_data = array_slice($evening_data, 0, 10);
        $evening_junction_names = array_map(function($item) use($junctions_map) {
            return $item['name'];
        }, array_slice($evening_data, 0, 3));

        return [
            'morning_peek' => [
                'info' => [
                    'desc' => sprintf($tpl, '早', count($morning_data), implode(',', $morning_junction_names), '早'),
                ],
                'center' => [
                    'lng' => round(array_sum(array_column($morning_data, 'lng')) / count(array_column($morning_data, 'lng')), 5),
                    'lat' => round(array_sum(array_column($morning_data, 'lat')) / count(array_column($morning_data, 'lat')), 5),
                ],
                'chart' => [
                    'title' => '平均延误对比',
                    'scale_title' => '平均延误(s)',
                    'series' => [
                        'name' => '早高峰',
                        'data' => $morning_data,
                    ],
                ],
            ],
            'evenint_peek' => [
                'info' => [
                    'desc' => sprintf($tpl, '晚', count($evening_data), implode(',', $evening_junction_names), '晚'),
                ],
                'center' => [
                    'lng' => round(array_sum(array_column($evening_data, 'lng')) / count(array_column($evening_data, 'lng')), 5),
                    'lat' => round(array_sum(array_column($evening_data, 'lat')) / count(array_column($evening_data, 'lat')), 5),
                ],
                'chart' => [
                    'title' => '平均延误对比',
                    'scale_title' => '平均延误(s)',
                    'series' => [
                        'name' => '晚高峰',
                        'data' => $evening_data,
                    ],
                ],
            ],
        ];
    }

    public function queryTopPI($params) {
    	$city_id = intval($params['city_id']);
    	$area_id = $params['area_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];


    	$area_detail = $this->areaService->getAreaDetail([
    		'city_id' => $city_id,
    		'area_id' => $area_id,
    	]);
    	$logic_junction_ids =array_column($area_detail['junction_list'], 'logic_junction_id');

        $theDatelist = $this->reportService->getDatesFromRange($start_date,$end_date);
        $theDatelist = $this->reportService->skipDate($theDatelist,$params['date_type']);



    	$morning_peek = $this->reportService->getMorningPeekRange($city_id, $logic_junction_ids, $theDatelist);
        // print_r($morning_peek);exit;
    	$morning_peek_hours = $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']);
    	$evening_peek = $this->reportService->getEveningPeekRange($city_id, $logic_junction_ids, $theDatelist);
    	$evening_peek_hours = $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']);
    	$peek_hours = array_merge($morning_peek_hours, $evening_peek_hours);

    	$morning_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, $logic_junction_ids, $theDatelist, $peek_hours);
    	usort($morning_pi_data, function($a, $b) {
    		return $a['pi'] > $b['pi'] ? -1 : 1;
    	});
    	return array_slice(array_column($morning_pi_data, 'logic_junction_id'), 0, 3);
    }

    private function getDateFromRange($startdate, $enddate,$date_type=0)
    {
        if ($startdate==$enddate){
            return [$startdate];
        }
        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);

        // 计算日期段内有多少天
        $days = ($etimestamp - $stimestamp) / 86400 + 1;
        // 保存每天日期
        $date = [];
        for ($i = 0; $i < $days; $i++) {
            $tmpdate = date('Y-m-d', $stimestamp + (86400 * $i));
            $week = date('w',$stimestamp + (86400 * $i));
            if($date_type == 1 && ($week == 0 || $week == 6)){
                continue;
            }elseif ($date_type == 2 && $week != 0 && $week != 6){
                continue;
            }else{
                $date[] = $tmpdate;
            }

        }
        return $date;
    }


    private function getTimeFromRange($st,$et,$step){
        $stimestamp = strtotime($st);
        $etimestamp = strtotime($et);
        $hours=[];
        for($i = $stimestamp;$i<=$etimestamp;$i+=$step*60){
            $hours[] = date('H:i', $i);
        }

        return $hours;
    }


    //时间前后取整
    private function roundingtime($time){
        $hour = date("H",strtotime($time));
        $min = date("i",strtotime($time));
        if($min < 30){
            $min = "00";
        }else{
            $min = "30";
        }
        return $hour.":".$min;
    }

    public function queryAreaAlarm($cityID,$areaID,$startTime,$endTime,$morningRushTime,$eveningRushTime,$date_type=0){
        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $cityID,
            'area_id' => $areaID,
        ]);

        $junctionList =array_column($area_detail['junction_list'], 'logic_junction_id');
//        $junctionList  = explode(",",$roadInfo['logic_junction_ids']);
        $juncNameMap=[];
        $rd=[];
        $rd['junction_info']=[];
        foreach ($area_detail['junction_list'] as $k => $j){
            $juncNameMap[$j['name']] = $j['logic_junction_id'];
            $rd['junctions_info'][$j['logic_junction_id']] = ['name'=>$j['name']];
        }

        $alarmInfo = $this->diagnosisNoTiming_model->getJunctionAlarmHoursData($cityID, $junctionList, $this->getDateFromRange($startTime,$endTime,$date_type));
        // print_r($alarmInfo);exit;
        //1: 过饱和 2: 溢流 3:失衡
        $imbalance=[];
        $oversaturation=[];
        $spillover=[];
        //路口报警统计
        foreach ($alarmInfo as $ak => $av){
            if(!in_array($av['logic_junction_id'],$junctionList)){
                continue;
            }
            //过滤报警不足5分钟的
            if(strtotime($av['end_time'])-strtotime($av['start_time']) < 5*60){
                continue;
            }
            switch ($av['type']){
                case 1:
                    $oversaturation[$av['logic_junction_id']][]=$av['start_time'];
                    break;
                case 2:
                    $spillover[$av['logic_junction_id']][]=$av['start_time'];
                    break;
                case 3:
                    $imbalance[$av['logic_junction_id']][]=$av['start_time'];
                    break;
            }
        }

        $imbalance = $this->sortSlice($imbalance);
        $oversaturation = $this->sortSlice($oversaturation);
        $spillover = $this->sortSlice($spillover);


        //初始化表格
        $initChartList = $this->roadReportService->initRoadAlarmChart($rd,$morningRushTime,$eveningRushTime,"区域");
        $fillChartData = $this->roadReportService->fillRoadAlarmChart($initChartList,$imbalance,$oversaturation,$spillover,$juncNameMap);
        // print_r($initChartList);
        // print_r($oversaturation);
        // print_r($juncNameMap);exit;
        return $fillChartData;
    }

    //各项指标数组进行排序,并保留最多10个
    private function sortSlice($orimap){
        $name = [];
        $count=[];
        foreach ($orimap as  $k=>$v){
            $name[] = $k;
            $count[] = count($v);
        }
        array_multisort($count,SORT_DESC,$name);
        $newMap=[];

        foreach ($count as $k => $v){
            $newMap[$name[$k]] = $orimap[$name[$k]];

        }
        return $newMap;

    }

    private function createHours(){
        $hours=[];
        for($i=strtotime("00:00");$i<=strtotime("23:30");$i=$i+1800){
            $hours[] = date("H:i",$i);
        }
        return $hours;
    }
    public function QueryAreaQuotaInfo($cityID,$roadID,$start_time,$end_time){
        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $cityID,
            'area_id' => $roadID,
        ]);
        $junctionIDs =array_column($area_detail['junction_list'], 'logic_junction_id');


        $dates = $this->getDateFromRange($start_time,$end_time);

        $roadQuotaData = $this->area_model->getJunctionsAllQuotaEs($dates,$junctionIDs,$cityID);


        $PiDatas = $this->pi_model->getGroupJuncPiWithDatesHours($cityID,$junctionIDs,$dates,$this->createHours());


        foreach ($PiDatas as $pk =>$pv){
            foreach ($roadQuotaData as $rk=>$rv){
                if($pk==$rv['hour']){
                    $roadQuotaData[$rk]['pi']=$pv;
                    break;
                }
            }
        }
        return $roadQuotaData;
    }

    public function saveThermograph($data,$res){
        if($res == false){
            return false;
        }
        $type=0;
        $res = json_decode($res,true);
        if($res['errorCode']!=0){
            return false;
        }
        if(isset($res['data']['figureTitle'])){
            $type=1;
        }
        if(isset($res['data']['videoTitle'])){
            $type=2;
        }
        $insertData=[
            'city_id'=>$data['city_id'],
            'area_id'=>$data['area_id'],
            'date'=>$data['date'],
            'hour'=>$data['hour'],
            'task_id'=>$res['data']['taskId'],
            'type'=>$type
        ];
        $ret =  $this->thermograph_model->save($insertData);
        return $ret;
    }

    //查询未知状态的任务
    public function queryUnreadyTask(){
        $where = [
            'status < '=>5
        ];
        $tasks = $this->thermograph_model->query($where);
        return array_column($tasks,'task_id');
    }
    //更新任务的状态
    public function updateUnreadyTasks($taskID,$status){
        return $this->thermograph_model->updateUnreadyTask($taskID,$status);
    }

    //查询热力图
    public function queryThermograph($url,$taskID,$morningRushTime){
        $ret = httpGET($url."?taskId=".$taskID);
        if($ret == false){
            return [];
        }

        $ret = json_decode($ret,true);
        if($ret['errorCode']!=0){
            return [];
        }
        $gifts = $ret['data']['giftUrls'];
        //根据早高峰过滤
        $st = $morningRushTime['s'];
        $et = $morningRushTime['e'];
        $glist = [];
        $flag = false;
        foreach ($gifts as $g){
            if(strstr($g,str_replace(":","",$st)."-")){
                $glist[] =$g;
                $flag = true;
            }elseif(strstr($g,"-".str_replace(":","",$et))){
                $glist[] =$g;
                break;
            }elseif($flag){
                $glist[] =$g;
            }
        }

        //临时兼容视频模块
        if(count($glist) == 0 && count($gifts)>0){
            $glist[] = $gifts[0];
        }
        return $glist;
    }


    public function queryThermographTaskID($cityID,$areaID,$startTime,$endTime,$type){
        $date = $startTime;
        if($startTime == $endTime){
            $date = $startTime;
        }else{
            $ds = $this->getDateFromRange($startTime,$endTime);
            foreach ($ds as $v){
                $week = date("w",strtotime($v));
                if($week == 1){
                    $date=$v;
                    break;
                }
            }
        }


        $query=[
            'city_id'=>$cityID,
            'area_id'=>$areaID,
            'date'=>$date,
            'type'=>$type,
        ];

        $ret = $this->thermograph_model->query($query);

        if(empty($ret)){
            return false;
        }
        $taskID = $ret[0]['task_id'];

        return ['task_id'=>$taskID,'date'=>$date];
    }

    //区域pi等级统计结果
    public function piLevelStatistics($cityID,$areaID,$startDate,$endDate){
        $cityID = (int)$cityID;
        //查询区域内全部路口
        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $cityID,
            'area_id' => $areaID,
        ]);

        $maxTotal = 1589;

        $junctionList =array_column($area_detail['junction_list'], 'logic_junction_id');

        $theDatelist = $this->getDateFromRange($startDate,$endDate);

        //查询本周期区域内全天pi数据
        $piDatas = $this->pi_model->getJunctionsPiWithDatesHours($cityID, $junctionList, $theDatelist,$this->reportService->getHoursFromRange("00:00","23:00"));
        $piLevelMap=['A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0];
        $total = 0;
        foreach ($piDatas as $pid){
            $total++;
            $piLevelMap[$this->pi_model->getPIlevel($pid['pi'])]++;
        }
        if($total > $maxTotal){
            $piLevelMap['A'] = $piLevelMap['A']  - ($total-$maxTotal);
        }

        //查询上个周期区域内全天pi数据
        $laststage = $this->getLastStage($startDate,$endDate);
        $theLastDatelist = $this->getDateFromRange($laststage[0],$laststage[1]);
        $lastPiDatas = $this->pi_model->getJunctionsPiWithDatesHours($cityID, $junctionList, $theLastDatelist,$this->reportService->getHoursFromRange("00:00","23:00"));
        $lastPiLevelMap=['A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0];
        $lasttotal=0;
        foreach ($lastPiDatas as $pid){
            $lasttotal++;
            $lastPiLevelMap[$this->pi_model->getPIlevel($pid['pi'])]++;
        }
        if($lasttotal > $maxTotal){
            $lastPiLevelMap['A'] = $lastPiLevelMap['A']  - ($lasttotal-$maxTotal);
        }


        $monA=0;
        $monB=0;
        $monC=0;
        $monD=0;
        $monE=0;
        if($lastPiLevelMap["A"] > 0){
            $monA = ($piLevelMap["A"] - $lastPiLevelMap["A"])/$lastPiLevelMap["A"];
        }
        if($lastPiLevelMap["B"] > 0){
            $monB = ($piLevelMap["B"] - $lastPiLevelMap["B"])/$lastPiLevelMap["B"];

        }
        if($lastPiLevelMap["C"] > 0){
            $monC = ($piLevelMap["C"] - $lastPiLevelMap["C"])/$lastPiLevelMap["C"];

        }
        if($lastPiLevelMap["D"] > 0){
            $monD = ($piLevelMap["D"] - $lastPiLevelMap["D"])/$lastPiLevelMap["D"];

        }
        if($lastPiLevelMap["E"] > 0){
            $monE = ($piLevelMap["E"] - $lastPiLevelMap["E"])/$lastPiLevelMap["E"];

        }

        return [
            ['level'=>"A","count"=>$piLevelMap['A'],"percent"=>$piLevelMap['A']/count($piDatas),"mon"=>$monA],
            ['level'=>"B","count"=>$piLevelMap['B'],"percent"=>$piLevelMap['B']/count($piDatas),"mon"=>$monB],
            ['level'=>"C","count"=>$piLevelMap['C'],"percent"=>$piLevelMap['C']/count($piDatas),"mon"=>$monC],
            ['level'=>"D","count"=>$piLevelMap['D'],"percent"=>$piLevelMap['D']/count($piDatas),"mon"=>$monD],
            ['level'=>"E","count"=>$piLevelMap['E'],"percent"=>$piLevelMap['E']/count($piDatas),"mon"=>$monE],
        ];
    }


    //济南需求 pi 优化和恶化排名
    public function piAnalysis($cityID,$areaID,$startDate,$endDate){
        $cityID = (int)$cityID;
        //查询区域内全部路口
        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $cityID,
            'area_id' => $areaID,
        ]);

        $junctionList =array_column($area_detail['junction_list'], 'logic_junction_id');

        $theDatelist = $this->getDateFromRange($startDate,$endDate);

        $piCompare = [];

        //本周数据
        $piDatas = $this->pi_model->getJunctionsPiWithDatesHours($cityID, $junctionList, $theDatelist,$this->reportService->getHoursFromRange("00:00","23:00"));

        foreach ($piDatas as $pk => $pid){
            if($pid['pi']>0){
                $piCompare[$pid['logic_junction_id']] = ['value'=>0,'sub'=>0];
                $piCompare[$pid['logic_junction_id']]['value'] = $pid['pi'];
                $piCompare[$pid['logic_junction_id']]['orivalue'] = $pid['pi'];
                $piCompare[$pid['logic_junction_id']]['orirank'] = $pk;
            }


        }
        //上周数据
        $laststage = $this->getLastStage($startDate,$endDate);
        $theLastDatelist = $this->getDateFromRange($laststage[0],$laststage[1]);
        $lastPiDatas = $this->pi_model->getJunctionsPiWithDatesHours($cityID, $junctionList, $theLastDatelist,$this->reportService->getHoursFromRange("00:00","23:00"));
        foreach ($lastPiDatas as $lpk => $lpid){
            if(isset($piCompare[$lpid['logic_junction_id']])){
                $piCompare[$lpid['logic_junction_id']]['lorivalue']  = $lpid['pi'];
                $piCompare[$lpid['logic_junction_id']]['lorirank']  = $lpk;
                $piCompare[$lpid['logic_junction_id']]['value'] = ($piCompare[$lpid['logic_junction_id']]['value'] -  $lpid['pi']);
                $piCompare[$lpid['logic_junction_id']]['sub'] = 1;
            }
        }

        $finalRank = [];
        foreach ($piCompare as $pk => $pv){
            if($pv['sub'] == 1){
                $finalRank[] = ['logic_junction_id'=>$pk,'value'=>$pv['value'],'orivalue'=>$pv['orivalue'],'lorivalue'=>$pv['lorivalue'],'orirank'=>$pv['orirank'],'lorirank'=>$pv['lorirank']];
            }
        }

        //降序排序
        usort($finalRank, function($a, $b) {
            return $a['value'] > $b['value'] ? -1 : 1;
        });
        //["logic_junction_name","logic_junction_id","pi","lastrank","stop_cycle_time","stop_delay","speed"]
        $junctions_map = [];
        array_map(function($item) use(&$junctions_map) {
            $junctions_map[$item['logic_junction_id']] = $item;
        }, $area_detail['junction_list']);

        $betterTop10= [];
        $worseTop10 = [];
        $quotaJunctions = [];
        for ($i=count($finalRank)-1;$i>0;$i--){
            if(count($betterTop10)>=20){ //多添加用作备用
                break;
            }
            if($finalRank[$i]['value'] > 0 ){
                break;
            }
            $betterTop10[] = [
                "logic_junction_id"=>$finalRank[$i]['logic_junction_id'],
                "name"=>$junctions_map[$finalRank[$i]['logic_junction_id']]['name'],
                "PI"=>round($finalRank[$i]['orivalue'],2),
                "last_pi"=>round($finalRank[$i]['lorivalue'],2),
                "last_rank"=>$finalRank[$i]['lorirank'],
                "rank"=>$finalRank[$i]['orirank'],
                "stop_time_cycle"=>"-",
                "stop_delay"=>"-",
                "speed"=>"-"
            ];
            $quotaJunctions[] = $finalRank[$i]['logic_junction_id'];

        }




        for($j=0;$j<count($finalRank)-1;$j++){
            if(count($worseTop10)>=20){ //多添加用作备用
                break;
            }
            if($finalRank[$j]['value'] <= 0 ){
                break;
            }
            $worseTop10[] = [
                "logic_junction_id"=>$finalRank[$j]['logic_junction_id'],
                "name"=>$junctions_map[$finalRank[$j]['logic_junction_id']]['name'],
                "PI"=>round($finalRank[$j]['orivalue'],2),
                "last_pi"=>round($finalRank[$j]['lorivalue'],2),
                "last_rank"=>$finalRank[$j]['lorirank'],
                "rank"=>$finalRank[$j]['orirank'],
                "stop_time_cycle"=>"-",
                "stop_delay"=>"-",
                "speed"=>"-"
            ];
            $quotaJunctions[] = $finalRank[$j]['logic_junction_id'];

        }


        //补充指标数据
        $quotadata = $this->dataService->call("/report/GetIndex", [
            'city_id' => $cityID,
            'dates' => $theDatelist,
            'logic_junction_ids' => $quotaJunctions,
            'hours' => $this->reportService->getHoursFromRange("00:00", "23:30"),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(stop_time_cycle * traj_count) AS stop_time_cycle, sum(speed * traj_count) AS speed, sum(traj_count) as traj_count",
            "group_by" => "logic_junction_id",
        ], "POST", 'json');
        $quota_data_map = [];
        array_map(function($item) use(&$quota_data_map) {
            $quota_data_map[$item['key']] = [
                'stop_delay' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
                'stop_time_cycle' => round($item['stop_time_cycle']['value'] / $item['traj_count']['value'], 2),
                'speed' => round($item['speed']['value'] / $item['traj_count']['value'] * 3.6, 2),
            ];
        }, $quotadata[2]);


        foreach ($betterTop10 as  $bk=>$bv){
            if(isset($quota_data_map[$bv['logic_junction_id']])){
                $betterTop10[$bk]['stop_time_cycle'] = $quota_data_map[$bv['logic_junction_id']]['stop_time_cycle'];
                $betterTop10[$bk]['stop_delay'] = $quota_data_map[$bv['logic_junction_id']]['stop_delay'];
                $betterTop10[$bk]['speed'] = $quota_data_map[$bv['logic_junction_id']]['speed'];
            }
        }
        $tmpBetter = [];
        foreach ($betterTop10 as $tpv){
            if(count($tmpBetter) >=10){
                break;
            }
            if($tpv['speed'] == "-"){
                continue;
            }
            $tmpBetter[] = $tpv;

        }
        $betterTop10 = $tmpBetter;

        $tmpWorse = [];
        foreach ($worseTop10 as $wk=>$wv){
            if(isset( $quota_data_map[$wv['logic_junction_id']])){
                $worseTop10[$wk]['stop_time_cycle'] = $quota_data_map[$wv['logic_junction_id']]['stop_time_cycle'];
                $worseTop10[$wk]['stop_delay'] = $quota_data_map[$wv['logic_junction_id']]['stop_delay'];
                $worseTop10[$wk]['speed'] = $quota_data_map[$wv['logic_junction_id']]['speed'];
            }
        }
        foreach ($worseTop10 as $tpv){
            if(count($tmpWorse) >=10){
                break;
            }
            if($tpv['speed'] == "-"){
                continue;
            }
            $tmpWorse[] = $tpv;

        }
        $worseTop10 = $tmpWorse;

        $tpl = "区域在分析日期内,%s情况最%s的%s个路口分别为%s";
        $juncStrtpl = "%s路口,本%s的PI值为%s,上%s的PI值为%s";


        if(count($theDatelist)==1){
            $stageType="日";
        }else if(count($theDatelist)==7){
            $stageType="周";
        }else if(count($theDatelist)<40){
            $stageType="月";
        }else{
            $stageType="季";
        }

        $betterTop3=[];

        if(count($betterTop10)>3){
            $betterTop3 = array_slice($betterTop10,0,3);
        }else{
            $betterTop3 = $betterTop10;
        }
        $worseTop3=[];
        if(count($worseTop10)>3){
            $worseTop3 = array_slice($worseTop10,0,3);
        }else{
            $worseTop3 = $worseTop10;
        }
        $finalBetterStr="";
        if(count($betterTop3)>0){
            $betterjuncs = "";
            foreach ($betterTop3 as $bv){

                $betterjuncs.= sprintf($juncStrtpl,$bv['name'],$stageType,$bv['pi'],$stageType,$bv['last_pi']);
            }
            $finalBetterStr= sprintf($tpl,"优化","好",count($betterTop3),$betterjuncs);
        }
        $finalWorseStr = "";
        if(count($worseTop3)>0){
            $worsejuncs = "";
            foreach ($worseTop3 as $wv){
                $worsejuncs.= sprintf($juncStrtpl,$wv['name'],$stageType,$wv['pi'],$stageType,$wv['last_pi']);
            }
            $finalWorseStr= sprintf($tpl,"恶化","严重",count($worseTop3),$worsejuncs);
        }



        return [
            'better_chart'=>$betterTop10,
            'better_desc'=>$finalBetterStr,
            'worse_chart'=>$worseTop10,
            'worse_desc'=>$finalWorseStr
        ];

    }

    public function piLevelTop5($cityID,$areaID,$startDate,$endDate){
        $cityID = (int)$cityID;
        //查询区域内全部路口
        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $cityID,
            'area_id' => $areaID,
        ]);

        $junctionList =array_column($area_detail['junction_list'], 'logic_junction_id');

        $junctions_info = $this->waymap_model->getJunctionInfo(implode(',', $junctionList));
        $junctions_map = [];
        array_map(function($item) use(&$junctions_map) {
            $junctions_map[$item['logic_junction_id']] = $item;
        }, $junctions_info);

        //查询早晚高峰
        $morning_peek = $this->reportService->getMorningPeekRange($cityID, $junctionList, $this->reportService->getDatesFromRange($startDate, $endDate));
        // print_r($morning_peek);exit;
        $morning_peek_hours = $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']);
        $evening_peek = $this->reportService->getEveningPeekRange($cityID, $junctionList, $this->reportService->getDatesFromRange($startDate, $endDate));
        $evening_peek_hours = $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']);
//        $peek_hours = array_merge($morning_peek_hours, $evening_peek_hours);


        $morningChartTop=["A"=>[],"B"=>[],"C"=>[],"D"=>[],"E"=>[]];
        $eveningChartTop=["A"=>[],"B"=>[],"C"=>[],"D"=>[],"E"=>[]];
        $alldayChartTop=["A"=>[],"B"=>[],"C"=>[],"D"=>[],"E"=>[]];
        //查询本周期区域内早高峰pi数据
        $morning_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($cityID, $junctionList, $this->reportService->getDatesFromRange($startDate, $endDate), $morning_peek_hours);
        usort($morning_pi_data, function($a, $b) {
            return $a['pi'] > $b['pi'] ? -1 : 1;
        });
        foreach ($morning_pi_data as $md){
            if(count($morningChartTop["A"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "A"){
                $morningChartTop["A"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($morningChartTop["B"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "B"){
                $morningChartTop["B"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($morningChartTop["C"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "C"){
                $morningChartTop["C"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($morningChartTop["D"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "D"){
                $morningChartTop["D"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($morningChartTop["E"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "E"){
                $morningChartTop["E"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }

        }


        //查询本周期区域内早高峰pi数据
        $evening_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($cityID, $junctionList, $this->reportService->getDatesFromRange($startDate, $endDate), $evening_peek_hours);
        usort($evening_pi_data, function($a, $b) {
            return $a['pi'] > $b['pi'] ? -1 : 1;
        });

        foreach ($evening_pi_data as $md){
            if(count($eveningChartTop["A"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "A"){
                $eveningChartTop["A"][] = $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($eveningChartTop["B"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "B"){
                $eveningChartTop["B"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($eveningChartTop["C"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "C"){
                $eveningChartTop["C"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($eveningChartTop["D"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "D"){
                $eveningChartTop["D"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($morningChartTop["E"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "E"){
                $eveningChartTop["E"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }

        }

        $allday_peek_hours =  $this->reportService->getHoursFromRange("00:00", "23:30");
        $allday_pi_data  = $this->pi_model->getJunctionsPiWithDatesHours($cityID, $junctionList, $this->reportService->getDatesFromRange($startDate, $endDate), $allday_peek_hours);
        usort($allday_pi_data, function($a, $b) {
            return $a['pi'] > $b['pi'] ? -1 : 1;
        });

        foreach ($allday_pi_data as $md){
            if(count($alldayChartTop["A"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "A"){
                $alldayChartTop["A"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($alldayChartTop["B"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "B"){
                $alldayChartTop["B"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($alldayChartTop["C"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "C"){
                $alldayChartTop["C"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($alldayChartTop["D"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "D"){
                $alldayChartTop["D"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }elseif (count($alldayChartTop["E"])<=5 && $this->pi_model->getPIlevel($md['pi']) == "E"){
                $alldayChartTop["E"][] =  $junctions_map[$md['logic_junction_id']]['name'];
            }

        }

        return [
            'morning_chart_top'=>$morningChartTop,
            'evening_chart_top'=>$eveningChartTop,
            'allday_chart_top'=>$alldayChartTop,
        ];


    }

}