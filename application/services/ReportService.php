<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/29
 * Time: 下午2:15
 */

namespace Services;

/**
 * Class ReportService
 * @package Services
 *
 * @property \Gift_model       $gift_model
 * @property \Report_model     $report_model
 * @property \UploadFile_model $uploadfile_model
 */
class ReportService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('report_model');
        $this->load->model('uploadfile_model');
        $this->load->model('gift_model');
        $this->load->model('waymap_model');
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
        $cityId  = $params['city_id'];
        $keyword = $params['keyword'];

        $topNum = 15; //限制返回前端数量

        $junctions = $this->waymap_model->getSuggestJunction($cityId, $keyword);

        $final_data = [];

        $count = 0;

        foreach ($junctions as $j) {
            if ($count >= $topNum) {
                break;
            }
            if (!$j['is_traffic']) {
                continue;
            }
            $final_data[] = [
                'logic_junction_id' => $j['logic_junction_id'],
                'lng' => $j['lng'],
                'lat' => $j['lat'],
                'city_id' => $cityId,
                'name' => $j['name'],
                'name_sim' => $j['name_sim'],
            ];

            $count += 1;
        }

        return $final_data;
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
        }

        return 'undefined type';
    }

    /**
     * @param $params
     *
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
            $this->uploadfile_model->insertUploadFile($param);
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
            $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $lastPos    = strrpos($currentUrl, '/');
            $baseUrl    = substr($currentUrl, 0, $lastPos);
            foreach ($result as $key => $item) {
                $itemInfo = $this->gift_model->getResourceUrlList($resourceKeys, $namespace);
                if (!empty($itemInfo[$item["file_key"]])) {
                    $result[$key]['url']      = $itemInfo[$item["file_key"]]['download_url'];
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
}