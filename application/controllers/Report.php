<?php
/**
 * 报告相关模块
 */


class Report extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('report_model');
        $this->load->library('EvaluateQuota');
    }

    public function test()
    {
        $evaluate = new EvaluateQuota();
        //1,model层获取基准数据
        $jdata = $this->report_model->test();
        //library层处理具体数据
        $ret = $evaluate->getJunctionDurationDelay($jdata,"start","end");
        return $this->response($ret);
    }

    public function reportConfig()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
                'type'        => 'nullunable',
            ]
        );
        if(!$validate['status']){
            return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
        }
        $type = $params['type'];
        switch ($type)
        {
            case 1:
                return $this->response($this->junctionReportConfig());
                break;
            case 2:
                return $this->response($this->junctionComparisonReportConfig());
                break;
            case 3:
                return $this->response($this->weekReportConfig());
                break;
            case 4:
                return $this->response($this->monthReportConfig());
                break;
        }
        return $this->response('undefined type');

    }

    public function junctionReportConfig()
    {
        return [
            'overview'=>[
                'title'=>'路口概览',
                'desc'=>'包括扫描路口在于路网中的区位',
                'items'=>[
                    [
                        'id'=>1,
                        'title'=>'各方向最大排队长度时间变化规律',
                        'desc'=>'平均各个日期各个方向最大排队长度在24小时中的随时间变化规律',
                        'quota_key'=>'queue_length'
                    ],
                    [
                        'id'=>2,
                        'title'=>'各方向延误时间变化规律',
                        'desc'=>'平均各个日期各个方向最大延误在24小时中随时间变化规律',
                        'quota_key'=>'stop_delay'
                    ],
                    [
                        'id'=>3,
                        'title'=>'各方向通过速度时间变化规律',
                        'desc'=>'平均各个日期各个方向最大通过速度在24小时中随时间变化规律',
                        'quota_key'=>'speed'
                    ],
                    [
                        'id'=>4,
                        'title'=>'各方向最大停车次数时间变化规律',
                        'desc'=>'平均各个日期各个方向最大停车次数在24小时中随时间变化规律',
                        'quota_key'=>'stop_time_cycle'
                    ],
                    [
                        'id'=>5,
                        'title'=>'各方向停车比率时间变化规律',
                        'desc'=>'平均各个日期各个方向停车比率在24小时中随时间变化规律',
                        'quota_key'=>'stop_rate'
                    ],
                    [
                        'id'=>6,
                        'title'=>'各方向溢流指数时间变化规律',
                        'desc'=>'平均各个日期各个方向溢流指数在24小时中随时间变化规律',
                        'quota_key'=>'spillover_rate'
                    ],

                ]
            ],
            'schedule'=>[
                [
                    'id'=>1,
                    'title'=>'各方向最大排队长度分析',
                    'desc'=>'平均各个日期中各方向最大排队长度在所在时段中随时间变化规律',
                    'quota_key'=>'queue_length'
                ],
                [
                    'id'=>2,
                    'title'=>'各方向延误分析',
                    'desc'=>'平均各个日期各方向最大延误所在时段中随时间变化规律',
                    'quota_key'=>'stop_delay'
                ],
                [
                    'id'=>3,
                    'title'=>'各方向通过速度分析',
                    'desc'=>'平均各个日期中各方向停车比率在所在时段中随时间变化规律',
                    'quota_key'=>'speed'
                ],
                [
                    'id'=>4,
                    'title'=>'各方向最大停车次数分析',
                    'desc'=>'对各方向最大停车次数在所在时段内的变化情况进行展示并分析',
                    'quota_key'=>'stop_time_cycle'
                ],
            ],
        ];

    }

    public function junctionComparisonReportConfig()
    {
        return $this->junctionReportConfig();
    }

    public function weekReportConfig()
    {
        return [
            'overview'=>[
                'title'=>'概览',
                'desc'=>'包括扫描本周发生溢流次数,过饱和次数,与上周数据对比进行情况描述',
                'items'=>[
                    [
                        'id'=>1,
                        'title'=>'延误最大top20路口分析',
                        'desc'=>'本周平均延误最大的20个路口展示',
                    ],
                    [
                        'id'=>2,
                        'title'=>'排队长度最大top20路口分析',
                        'desc'=>'本周最大排队长度top20路口展示',
                    ],
                    [
                        'id'=>3,
                        'title'=>'溢流问题分析',
                        'desc'=>'对比本周溢流发生次数在24小时情况对比,以及对比上周平均情况对比',
                    ],
                    [
                        'id'=>4,
                        'title'=>'工作日早高峰分析(6:30 ~ 9:30)',
                        'desc'=>'延误最大top10,排队长度最大top10路口数据与上周排名进行对比,并分析趋势',
                    ],
                    [
                        'id'=>5,
                        'title'=>'工作日晚高峰分析(16:30 ~ 19:30)',
                        'desc'=>'延误最大top10,排队长度最大top10路口数据与上周排名进行对比,并分析趋势',
                    ],
                ]
            ]
        ];
    }

    public function monthReportConfig()
    {
        return [
            'overview'=>[
                'title'=>'概览',
                'desc'=>'包括扫描本月发生溢流次数,过饱和次数,与上月数据对比进行情况描述',
                'items'=>[
                    [
                        'id'=>1,
                        'title'=>'延误最大top20路口分析',
                        'desc'=>'本月平均延误最大的20个路口展示',
                    ],
                    [
                        'id'=>2,
                        'title'=>'排队长度最大top20路口分析',
                        'desc'=>'本月最大排队长度top20路口展示',
                    ],
                    [
                        'id'=>3,
                        'title'=>'溢流问题分析',
                        'desc'=>'对比本月溢流发生次数在24小时情况对比,以及对比上月平均情况对比',
                    ],
                    [
                        'id'=>4,
                        'title'=>'工作日早高峰分析(6:30 ~ 9:30)',
                        'desc'=>'延误最大top10,排队长度最大top10路口数据与上月排名进行对比,并分析趋势',
                    ],
                    [
                        'id'=>5,
                        'title'=>'工作日晚高峰分析(16:30 ~ 19:30)',
                        'desc'=>'延误最大top10,排队长度最大top10路口数据与上月排名进行对比,并分析趋势',
                    ],
                ]
            ]
        ];
    }
}