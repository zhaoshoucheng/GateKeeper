<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/29
 * Time: 下午2:15
 */

namespace Services;

use Services\DataService;

/**
 * Class ReportService
 * @package Services
 *
 * @property \Gift_model       $gift_model
 * @property \Report_model     $report_model
 * @property \UploadFile_model $uploadFile_model
 */
class ReportService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('report_model');
        $this->load->model('uploadFile_model');
        $this->load->model('gift_model');
        $this->load->model('waymap_model');
        $this->load->model('road_model');
        $this->load->model('area_model');

        $this->report_proxy = $this->config->item('report_proxy');

        $this->dataService = new DataService();
    }

    /**
     * @return array
     */
    public function test()
    {
        $data = [
            [
                'start_time' => "12:00:00",
                'end_time' => "13:00:00",
                'logic_junction_id' => "123456",
                'stop_delay' => "2.3333",
            ], [
                'start_time' => "13:00:00",
                'end_time' => "14:00:00",
                'logic_junction_id' => "1234567",
                'stop_delay' => "1.2222",
            ],
        ];

        $evaluate = new \EvaluateQuota();

        return $evaluate->getJunctionDurationDelay($data, "start", "end");
    }

    /**
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function searchJunction($params)
    {
        $majorJunctionID = "";
        if(!empty($_SERVER["HTTP_REFERER"])){
            parse_str($_SERVER["HTTP_REFERER"],$arrs);
            if (isset($arrs["junctionId"])) {
                $majorJunctionID=$arrs["junctionId"];
            }
        }
        $cityId  = $params['city_id'];
        $keyword = $params['keyword'];

        $topNum = 15; //限制返回前端数量
        try{
            $junctions = $this->waymap_model->getSuggestJunction($cityId, $keyword);
        }catch (\Exception $e){
            return [];
        }
        $final_data = [];

        $count = 0;
        $majorJunctionInfo = [];


        //数据过滤
        $restrictJuncs = $this->waymap_model->getRestrictJunctionCached($cityId);
        $mapRestrictJuncs = array_flip($restrictJuncs);

        $junctions = array_filter($junctions, function($item) use($mapRestrictJuncs){
            if(empty($mapRestrictJuncs)){
                return true;
            }
            if(isset($mapRestrictJuncs[$item['logic_junction_id']])){
                return true;
            }
            return false;
        });
        foreach ($junctions as $j) {
            if ($count >= $topNum) {
                break;
            }
            if (!$j['is_traffic']) {
                continue;
            }

            $jInfo = [
                'logic_junction_id' => $j['logic_junction_id'],
                'lng' => $j['lng'],
                'lat' => $j['lat'],
                'city_id' => $cityId,
                'name' => $j['name'],
                'name_sim' => $j['name_sim'],
            ];
            if($jInfo["logic_junction_id"]==$majorJunctionID){
                $majorJunctionInfo = $jInfo;
            }else{
                $final_data[] = [
                    'logic_junction_id' => $j['logic_junction_id'],
                    'lng' => $j['lng'],
                    'lat' => $j['lat'],
                    'city_id' => $cityId,
                    'name' => $j['name'],
                    'name_sim' => $j['name_sim'],
                ];
            }
            $count += 1;
        }
        if(!empty($majorJunctionInfo)){
            array_unshift($final_data,$majorJunctionInfo);
        }
        return $final_data;
    }

    public function searchRoad($params) {
        $city_id = $params['city_id'];
        $keyword = $params['keyword'];
        $road_list =$this->road_model->searchRoadsByKeyword($city_id, $keyword);
        $road_list = array_map(function($item) {
            return [
                'road_id' => $item['road_id'],
                'road_name' => $item['road_name'],
            ];
        }, $road_list);
        return $road_list;
    }

    public function searchArea($params) {
        $city_id = $params['city_id'];
        $keyword = $params['keyword'];
        $area_list =$this->area_model->searchAreasByKeyword($city_id, $keyword);
        $area_list = array_map(function($item) {
            return [
                'area_id' => $item['id'],
                'area_name' => $item['area_name'],
            ];
        }, $area_list);
        return $area_list;
    }

    /**
     * @param $params
     *
     * @return array|string
     */
    public function reportConfig($params)
    {
        $junctionReportConfig           = [
            'overview' => [
                'title' => '路口概览',
                'desc' => '包括扫描路口在于路网中的区位',
                'items' => [
                    [
                        'id' => 1,
                        'title' => '各方向最大排队长度时间变化规律',
                        'desc' => '平均各个日期各个方向最大排队长度在24小时中的随时间变化规律',
                        'quota_key' => 'queue_length',
                    ],
                    [
                        'id' => 2,
                        'title' => '各方向延误时间变化规律',
                        'desc' => '平均各个日期各个方向最大延误在24小时中随时间变化规律',
                        'quota_key' => 'stop_delay',
                    ],
                    [
                        'id' => 3,
                        'title' => '各方向通过速度时间变化规律',
                        'desc' => '平均各个日期各个方向最大通过速度在24小时中随时间变化规律',
                        'quota_key' => 'speed',
                    ],
                    [
                        'id' => 4,
                        'title' => '各方向最大停车次数时间变化规律',
                        'desc' => '平均各个日期各个方向最大停车次数在24小时中随时间变化规律',
                        'quota_key' => 'stop_time_cycle',
                    ],
                    [
                        'id' => 5,
                        'title' => '各方向停车比率时间变化规律',
                        'desc' => '平均各个日期各个方向停车比率在24小时中随时间变化规律',
                        'quota_key' => 'stop_rate',
                    ],
                    [
                        'id' => 6,
                        'title' => '各方向溢流指数时间变化规律',
                        'desc' => '平均各个日期各个方向溢流指数在24小时中随时间变化规律',
                        'quota_key' => 'spillover_rate',
                    ],

                ],
            ],
            'schedule' => [
                [
                    'id' => 1,
                    'title' => '各方向最大排队长度分析',
                    'desc' => '平均各个日期中各方向最大排队长度在所在时段中随时间变化规律',
                    'quota_key' => 'queue_length',
                ],
                [
                    'id' => 2,
                    'title' => '各方向延误分析',
                    'desc' => '平均各个日期各方向最大延误所在时段中随时间变化规律',
                    'quota_key' => 'stop_delay',
                ],
                [
                    'id' => 3,
                    'title' => '各方向通过速度分析',
                    'desc' => '平均各个日期中各方向停车比率在所在时段中随时间变化规律',
                    'quota_key' => 'speed',
                ],
                [
                    'id' => 4,
                    'title' => '各方向最大停车次数分析',
                    'desc' => '对各方向最大停车次数在所在时段内的变化情况进行展示并分析',
                    'quota_key' => 'stop_time_cycle',
                ],
            ],
        ];
        $junctionComparisonReportConfig = [
            'schedule' => [
                [
                    'id' => 1,
                    'title' => '各方向最大排队长度分析',
                    'desc' => '平均各个日期中各方向最大排队长度在所在时段中随时间变化规律',
                    'quota_key' => 'queue_length',
                ],
                [
                    'id' => 2,
                    'title' => '各方向延误分析',
                    'desc' => '平均各个日期各方向最大延误所在时段中随时间变化规律',
                    'quota_key' => 'stop_delay',
                ],
                [
                    'id' => 3,
                    'title' => '各方向通过速度分析',
                    'desc' => '平均各个日期中各方向停车比率在所在时段中随时间变化规律',
                    'quota_key' => 'speed',
                ],
                [
                    'id' => 4,
                    'title' => '各方向最大停车次数分析',
                    'desc' => '对各方向最大停车次数在所在时段内的变化情况进行展示并分析',
                    'quota_key' => 'stop_time_cycle',
                ],
            ],
        ];
        $weekReportConfig               = [
            'overview' => [
                'title' => '概览',
                'desc' => '包括扫描本周发生溢流次数,过饱和次数,与上周数据对比进行情况描述',
                'items' => [
                    [
                        'id' => 1,
                        'title' => '各行政区分析',
                        'desc' => '各行政区不同时段平均延误、平均速度对比',
                        'quota_key' => 'district',
                        'api_info' => [
                            'key' => 2,
                            'type' => 3,
                        ],
                    ],
                    [
                        'id' => 2,
                        'title' => '延误top20路口分析',
                        'desc' => '本周平均延误最大的20个路口展示',
                        'quota_key' => 'stop_delay_day_20',
                        'api_info' => [
                            'key' => 0,
                            'type' => 3,
                            'time_type' => 1,
                            'top_num' => 20,
                            'quota_key' => ['stop_delay'],
                        ],
                    ],
                    [
                        'id' => 3,
                        'title' => '排队长度top20路口分析',
                        'desc' => '本周最大排队长度top20路口展示',
                        'quota_key' => 'queue_length_day_20',
                        'api_info' => [
                            'key' => 0,
                            'type' => 3,
                            'time_type' => 1,
                            'top_num' => 20,
                            'quota_key' => ['queue_length'],
                        ],
                    ],
                    [
                        'id' => 4,
                        'title' => '溢流问题分析',
                        'desc' => '对比本周溢流发生次数在24小时情况对比,以及对比上周平均情况对比',
                        'quota_key' => 'spillover',
                        'api_info' => [
                            'key' => 1,
                            'type' => 3,
                            'quota_key' => ['spillover'],
                        ],
                    ],
                    [
                        'id' => 5,
                        'title' => '工作日早高峰分析(6:30 ~ 9:30)',
                        'desc' => '延误top10,排队长度top10路口数据与上周排名进行对比,并分析趋势',
                        'quota_key' => 'quota_morning_10',
                        'api_info' => [
                            'key' => 0,
                            'type' => 3,
                            'time_type' => 2,
                            'top_num' => 10,
                            'quota_key' => ['stop_delay', 'queue_length'],
                        ],
                    ],
                    [
                        'id' => 6,
                        'title' => '工作日晚高峰分析(16:30 ~ 19:30)',
                        'desc' => '延误top10,排队长度top10路口数据与上周排名进行对比,并分析趋势',
                        'quota_key' => 'quota_night_10',
                        'api_info' => [
                            'key' => 0,
                            'type' => 3,
                            'time_type' => 3,
                            'top_num' => 10,
                            'quota_key' => ['stop_delay', 'queue_length'],
                        ],
                    ],
                ],
            ],
        ];
        $monthReportConfig              = [
            'overview' => [
                'title' => '概览',
                'desc' => '包括扫描本月发生溢流次数,过饱和次数,与上月数据对比进行情况描述',
                'items' => [
                    [
                        'id' => 1,
                        'title' => '各行政区分析',
                        'desc' => '各行政区不同时段平均延误、平均速度对比',
                        'quota_key' => 'district',
                        'api_info' => [
                            'key' => 2,
                            'type' => 4,
                        ],
                    ],
                    [
                        'id' => 2,
                        'title' => '延误top20路口分析',
                        'desc' => '本月平均延误最大的20个路口展示',
                        'quota_key' => 'stop_delay_day_20',
                        'api_info' => [
                            'key' => 0,
                            'type' => 4,
                            'time_type' => 1,
                            'top_num' => 20,
                            'quota_key' => ['stop_delay'],
                        ],
                    ],
                    [
                        'id' => 3,
                        'title' => '排队长度top20路口分析',
                        'desc' => '本月最大排队长度top20路口展示',
                        'quota_key' => 'queue_length_day_20',
                        'api_info' => [
                            'key' => 0,
                            'type' => 4,
                            'time_type' => 1,
                            'top_num' => 20,
                            'quota_key' => ['queue_length'],
                        ],
                    ],
                    [
                        'id' => 4,
                        'title' => '溢流问题分析',
                        'desc' => '对比本月溢流发生次数在24小时情况对比,以及对比上月平均情况对比',
                        'quota_key' => 'spillover',
                        'api_info' => [
                            'key' => 1,
                            'type' => 4,
                        ],
                    ],
                    [
                        'id' => 5,
                        'title' => '工作日早高峰分析(6:30 ~ 9:30)',
                        'desc' => '延误top10,排队长度top10路口数据与上月排名进行对比,并分析趋势',
                        'quota_key' => 'quota_morning_10',
                        'api_info' => [
                            'key' => 0,
                            'type' => 4,
                            'time_type' => 2,
                            'top_num' => 10,
                            'quota_key' => ['stop_delay', 'queue_length'],
                        ],
                    ],
                    [
                        'id' => 6,
                        'title' => '工作日晚高峰分析(16:30 ~ 19:30)',
                        'desc' => '延误top10,排队长度top10路口数据与上月排名进行对比,并分析趋势',
                        'quota_key' => 'quota_night_10',
                        'api_info' => [
                            'key' => 0,
                            'type' => 4,
                            'time_type' => 3,
                            'top_num' => 10,
                            'quota_key' => ['stop_delay', 'queue_length'],
                        ],
                    ],
                ],
            ],
        ];

        $newJunctionReportConfig=[
            'overview' => [
                'title' => '路口概览',
                'desc' => '路口报告',
                'items' => [
                    [
                        'id' => 1,
                        'title' => '路口运行状况对比',
                        'desc' => '本周路口平均延误与上周对比',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 2,
                        'title' => '路口运行情况',
                        'desc' => '路口全天24小时各项运行指标分析',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 3,
                        'title' => '区域重点路口运行指标分析',
                        'desc' => '区域重点路口整体以及分转向的全天24小时指标分析',
                        'quota_key' => '',
                    ],
                ],
            ]
        ];
        $newRoadReportConfig=[
            'overview' => [
                'title' => '干线报告',
                'desc' => '干线报告',
                'items' => [
                    [
                        'id' => 1,
                        'title' => '干线运行状况对比',
                        'desc' => '本周干线路口平均延误与上周对比',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 2,
                        'title' => '干线运行情况',
                        'desc' => '干线全天24小时各项运行指标分析',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 3,
                        'title' => '干线协调效果',
                        'desc' => '干线不同时段协调效果分析',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 4,
                        'title' => '干线拥堵情况分析',
                        'desc' => '干线不同时段路口拥堵情况可视化',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 5,
                        'title' => '干线路口报警总结',
                        'desc' => '干线不同时段报警持续5分钟以上的路口分析',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 6,
                        'title' => '干线路口运行指数排名',
                        'desc' => '干线不同时段PI指数排名路口各项运行指标汇总',
                        'quota_key' => '',
                    ],

                ],
            ]
        ];
        $newAreaReportConfig=[
            'overview' => [
                'title' => '区域概览',
                'desc' => '区域报告相关',
                'items' => [
                    [
                        'id' => 1,
                        'title' => '区域运行状况对比',
                        'desc' => '本周区域路口平均延误与上周对比',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 2,
                        'title' => '区域运行情况',
                        'desc' => '区域全天24小时各项运行指标分析',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 3,
                        'title' => '轨迹热力演变',
                        'desc' => '区域早高峰运行状况演变过程',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 4,
                        'title' => '区域拥堵情况分析',
                        'desc' => '区域不同时段路口拥堵情况可视化',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 5,
                        'title' => '区域路口报警总结',
                        'desc' => '区域不同时段持续5分钟以上的路口分析',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 6,
                        'title' => '区域路口运行指数排名',
                        'desc' => '区域不同时段PI指数排名前20的路口各项运行指标汇总',
                        'quota_key' => '',
                    ],
                    [
                        'id' => 7,
                        'title' => '区域重点路口运行指标分析',
                        'desc' => '区域重点路口整体以及分转向的全天24小时指标分析',
                        'quota_key' => '',
                    ],

                ],
            ]
        ];

        $type = $params['type'];

        switch ($type) {
            case 1:
                return $junctionReportConfig;
            case 2:
                return $junctionComparisonReportConfig;
            case 3:
                return $weekReportConfig;
            case 4:
                return $monthReportConfig;
            case 10:
                return $newJunctionReportConfig;
            case 11:
                return $newRoadReportConfig;
            case 12:
                return $newAreaReportConfig;
        }

        return 'undefined type';
    }

    /**
     * 生成报告
     * @param $params['city_id'] int    城市ID
     * @param $params['title']   string 报告标题
     * @param $params['type']    int    报告类型 1，路口分析报告；2，路口优化对比报告；3，城市分析报告（周报）；4，城市分析报告（月报）
     * @param $params['file']    binary 二进制文件
     * @return mixed
     * @throws \Exception
     */
    public function generate($params)
    {
        $cityId = $params['city_id'];
        $type   = $params['type'];
        $title  = $params['title'];

        //上传图片
        $data = $this->gift_model->Upload("file");

        $retryNum = 0;

        while (true) {

            if ($retryNum > 1000) {
                throw new \Exception("生成的报告太多.");
            }

            $num = $this->report_model->countReportByTitle($cityId, $title, $type);

            if ($num == 0) {
                break;
            }

            $title = str_replace("(" . $retryNum . ")", '', $title) . "(" . ($retryNum + 1) . ")";
            $retryNum++;
        }

        //插入
        $itemId = $this->report_model->insertReport($params);

        //插入新记录
        foreach ($data as $namespace => $item) {
            $param = [
                "file_key" => $item['resource_key'],
                "item_id" => $itemId,
                "namespace" => $namespace,
                "b_type" => 1,
            ];
            $this->uploadFile_model->insertUploadFile($param);
        }

        return $data['itstool_private'];
    }


    /**
     * @param $params
     *
     * @return array
     */
    public function getReportList($params)
    {
        $cityId = $params['city_id'];
        $type = $params['type'];
        $pageNum = $params['page_no'];
        $pageSize = $params['page_size'];

        $namespace = 'itstool_private';

        $statRow = $this->report_model->getCountJoinUploadFile($cityId, $type, $pageNum, $pageSize, $namespace);

        $result = $this->report_model->getSelectJoinUploadFile($cityId, $type, $pageNum, $pageSize, $namespace);

        $formatResult = function ($result) use ($statRow, $namespace, $pageNum, $pageSize) {
            $resourceKeys = array_reduce($result, function ($carry, $item) {
                if (!empty($item["file_key"])) {
                    $carry[] = $item["file_key"];
                }
                return $carry;
            }, []);

            $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

            $hostName = $_SERVER['HTTP_HOST'];
            if($_SERVER['REMOTE_ADDR']=="59.52.254.218"){
                $hostName = "59.52.254.216:91";
            }
            $currentUrl = $protocol . $hostName . $_SERVER['REQUEST_URI'];
            $lastPos    = strrpos($currentUrl, '/');
            $baseUrl    = substr($currentUrl, 0, $lastPos);
//            $baseUrl    = $this->report_proxy;
            foreach ($result as $key => $item) {
                $itemInfo = $this->gift_model->getResourceUrlList($resourceKeys, $namespace);
                if (!empty($itemInfo[$item["file_key"]])) {
                    $result[$key]['url']      = $itemInfo[$item["file_key"]]['download_url'];
//                    $result[$key]['url']      = $baseUrl."/Report/reportProxy?url=".base64_encode($itemInfo[$item["file_key"]]['download_url']);
                    $result[$key]['down_url'] = $baseUrl . "/downReport?key=" . $item["file_key"];
                }
            }
            return [
                "list" => $result,
                "total" => $statRow['num'],
                "page_no" => $pageNum,
                "page_size" => $pageSize,
            ];
        };
        return $formatResult($result);
    }

    /**
     * @param $params
     *
     * @throws \Exception
     */
    public function downReport($params)
    {
        $this->gift_model->downResource($params["key"], 'itstool_public');
    }

    // 1 日报；2 周报；3 月报；4 季报；0 invalid
    public function report_type($start_date, $end_date) {
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);
        if ($start_time == $end_time) {
            return 1;
        } elseif ($end_time - $start_time == 86400 * 6) {
            return 2;
        } elseif (date('Y-m-01', $start_time) == $start_date) {
            $start_month = date('m', $start_time);
            $end_month = date('m', $end_time);
            if (date('Y-m-d', strtotime("$start_date +1 month -1 day")) == $end_date) {
                return 3;
            } elseif (date('Y-m-d', strtotime("$start_date +4 month -1 day")) == $end_date) {
                return 4;
            }
        }
        return 0;
    }

    public function last_report_date($start_date, $end_date, $report_type) {
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);
        $last_start_date = $start_date;
        $last_end_date = $end_date;
        if ($report_type == 1) {
            $last_start_date = date('Y-m-d', $start_time - 86400);
            $last_end_date = date('Y-m-d', $end_time - 86400);
        } elseif ($report_type == 2) {
            $last_start_date = date('Y-m-d', $start_time - 86400 * 7);
            $last_end_date = date('Y-m-d', $end_time - 86400 * 7);
        } elseif ($report_type == 3) {
            $last_start_date = date('Y-m-01', strtotime("$start_date -1 month"));
            $last_end_date = date('Y-m-d', strtotime("$last_start_date +1 month -1 day"));
        } elseif ($report_type == 4) {
            $last_start_date = date('Y-m-01', strtotime("$start_date -3 month"));
            $last_end_date = date('Y-m-d', strtotime("$last_start_date +1 month -1 day"));
        }
        return [
            'start_date' => $last_start_date,
            'end_date' => $last_end_date,
        ];
    }

    public function getComparisonText($now, $last, $report_type) {
        $text = [];
        $s1 = array_sum($now) / count($now);
        $s2 = array_sum($last) / count($last);
        if ($s1 / $s2 - $s2 >= 0.01) {
            $text[] = "更加严重";
        }elseif ($s1 / $s2 - $s2 <= -0.01) {
            $text[] = "得到缓解";
        }else {
            $text[] = "基本持平";
        }
        if ($report_type == 1) {
            $text[] = '今天';
            $text[] = '昨天';
        } elseif ($report_type == 2) {
            $text[] = '本周';
            $text[] = '上周';
        } elseif ($report_type == 3) {
            $text[] = '本月';
            $text[] = '上月';
        } elseif ($report_type == 4) {
            $text[] = '本季度';
            $text[] = '上季度';
        }
        return $text;
    }

    public function getDatesFromRange($start_date, $end_date) {
        $dates = [];
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);
        for ($t = $start_time; $t <= $end_time; $t += 86400) {
            $dates[] = date('Y-m-d', $t);
        }
        return $dates;
    }

    public function getHoursFromRange($start_hour, $end_hour) {
        $hs = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];
        $ms = ['00', '30'];

        $hours = [];
        foreach ($hs as $h) {
            foreach ($ms as $m) {
                $hm = $h . ':' . $m;
                if ($hm >= $start_hour and $hm < $end_hour) {
                    $hours[] = $hm;
                }
            }
        }
        return $hours;
    }

    // {x : hh:mm, y : value}
    // 48个时刻点补全 null
    public function addto48($objs) {
        $hs = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];
        $ms = ['00', '30'];

        $full = [];
        foreach ($hs as $h) {
            foreach ($ms as $m) {
                $full[] = $h . ':' . $m;
            }
        }

        $hms = array_column($objs, 'x');
        $diff = array_diff($full, $hms);
        foreach ($diff as $one) {
            $objs[] = [
                'x' => $one,
                'y' => null,
            ];
        }
        usort($objs, function($a, $b) {
            return ($a['x'] < $b['x']) ? -1 : 1;
        });
        return $objs;
    }

    // 早晚高峰需要写两个很大的if else，分开写吧
    // 早高峰开始结束时间
    public function getMorningPeekRange($city_id, $logic_junction_ids, $dates) {
        $hours = ['07:00', '07:30', '09:00', '09:30'];
        $data = $this->dataService->call("/report/GetStopDelayByHour", [
            'city_id' => $city_id,
            'dates' => $dates,
            'logic_junction_ids' => $logic_junction_ids,
            'hours' => $hours,
        ], "POST", 'json');

        $data = array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $data[2]);

        if (count($data) != 4) {
            return [
                'start_hour' => '07:00',
                'end_hour' => '09:00',
            ];
        }

        $t = [];
        for ($i = 0; $i < 3; $i ++) {
            $t[] = $data[$i] + $data[$i + 1];
        }
        if ($t[0] >= $t[1] and $t[0] >= $t[2]) {
            return [
                'start_hour' => '07:00',
                'end_hour' => '09:00',
            ];
        }
        if ($t[2] >= $t[0] and $t[2] >= $t[2]) {
            return [
                'start_hour' => '08:00',
                'end_hour' => '10:00',
            ];
        }
        return [
            'start_hour' => '07:30',
            'end_hour' => '09:30',
        ];
    }

    // 晚高峰开始结束时间
    public function getEveningPeekRange($city_id, $logic_junction_ids, $dates) {
        $hours = ['07:00', '07:30', '09:00', '09:30'];
        $data = $this->dataService->call("/report/GetStopDelayByHour", [
            'city_id' => $city_id,
            'dates' => $dates,
            'logic_junction_ids' => $logic_junction_ids,
            'hours' => $hours,
        ], "POST", 'json');

        $data = array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $data[2]);

        if (count($data) != 4) {
            return [
                'start_hour' => '17:00',
                'end_hour' => '19:00',
            ];
        }

        $t = [];
        for ($i = 0; $i < 3; $i ++) {
            $t[] = $data[$i] + $data[$i + 1];
        }
        if ($t[0] >= $t[1] and $t[0] >= $t[2]) {
            return [
                'start_hour' => '17:00',
                'end_hour' => '19:00',
            ];
        }
        if ($t[2] >= $t[0] and $t[2] >= $t[2]) {
            return [
                'start_hour' => '18:00',
                'end_hour' => '20:00',
            ];
        }
        return [
            'start_hour' => '17:30',
            'end_hour' => '19:30',
        ];
    }
}