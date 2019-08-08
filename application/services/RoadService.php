<?php
/**
 * 信控平台 - 干线相关接口
 *
 * User: lichaoxi_i@didiglobal.com
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class RoadService
 * @package Services
 * @property \Road_model $road_model
 */
class RoadService extends BaseService
{
    /**
     * RoadService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('waymap_model');
        $this->load->model('redis_model');
        $this->load->model('road_model');
        $this->load->model('flowDurationV6_model');

        $this->load->config('evaluate_conf');
    }

    /**
     * 获取城市干线列表
     *
     * @param $params
     *
     * @return array
     */
    public function getRoadList($params)
    {
        $cityId = $params['city_id'];

        $select = 'road_id, road_name, road_direction';

        return $this->road_model->getRoadsByCityId($cityId, $select);
    }

    /**
     * 由干线自增id获取干线子路口
     *
     * @param $params
     *
     * @return array
     */
    public function getJunctionsByRoadID($params)
    {
        $roadIDs = $params['road_ids'];

        return $this->road_model->getJunctionsByRoadID($roadIDs);
    }

    /**
     * 新增干线
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function addRoad($params)
    {
        $cityId = $params['city_id'];
        $junctionIds = $params['junction_ids'];
        $roadName = $params['road_name'];

        $data = [
            'city_id' => intval($cityId),
            'road_id' => md5(implode(',', $junctionIds) . $roadName),
            'road_name' => strip_tags(trim($roadName)),
            'logic_junction_ids' => implode(',', $junctionIds),
            'user_id' => 0,
        ];

        if (!$this->road_model->roadNameIsUnique($roadName, $cityId)) {
            throw new \Exception('干线名称 ' . $roadName . ' 已经存在', ERR_DATABASE);
        }

        $res = $this->road_model->insertRoad($data);

        if (!$res) {
            throw new \Exception('新增干线失败', ERR_PARAMETERS);
        }

        return $res;
    }

    /**
     * 更新干线
     *
     * @param $params
     *
     * @return bool
     * @throws \Exception
     */
    public function updateRoad($params)
    {
        $cityId = $params['city_id'];
        $roadId = $params['road_id'];
        $junctionIds = $params['junction_ids'];
        $roadName = $params['road_name'];

        $data = [
            'road_name' => strip_tags(trim($roadName)),
            'logic_junction_ids' => implode(',', $junctionIds),
        ];

        if (!$this->road_model->roadNameIsUnique($roadName, $cityId, $roadId)) {
            throw new \Exception('干线名称 ' . $roadName . ' 已经存在', ERR_DATABASE);
        }

        $res = $this->road_model->updateRoad($roadId, $data);

        $this->redis_model->delList('Road_' . $roadId);
        $this->redis_model->delList('Road_extend_' . $roadId);

        if (!$res) {
            throw new \Exception('更新干线失败', ERR_PARAMETERS);
        }

        return $res;
    }

    /**
     * 删除干线
     *
     * @param $params
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteRoad($params)
    {
        $roadId = $params['road_id'];

        $res = $this->road_model->deleteRoad($roadId);

        $this->redis_model->delList('Road_' . $roadId);
        $this->redis_model->delList('Road_extend_' . $roadId);

        if (!$res) {
            throw new \Exception('删除干线失败', ERR_PARAMETERS);
        }

        return $res;
    }

    /**
     * 获取path的上下游路口
     *
     * @param $params
     * @return array
     * @throws \Exception
     */
    public function getPathHeadTailJunction($params)
    {
        $cityId = $params["city_id"];
        $junctionIdList = explode(",",$params['junction_ids']);
        $juncMovements = $this->waymap_model->getFlowMovement($cityId, $junctionIdList[0], 'all', 1);
        //上游路口
        $up_road_degree = [];
        foreach ($juncMovements as $item) {
            if ($item['junction_id'] == $junctionIdList[0] &&
                $item['downstream_junction_id'] == $junctionIdList[1]
            ) {
                $absDiff = abs(floatval($item['in_degree']) - floatval($item['out_degree']));
                if($absDiff>180){
                    $absDiff = 360-$absDiff;
                }
                $up_road_degree[$item['upstream_junction_id']] = $absDiff;
            }
        }
        if (!empty($up_road_degree)) {
            asort($up_road_degree); //按照键值进行升序排序
            array_unshift($junctionIdList, (string)key($up_road_degree));   //头部插入
        } else {
            //throw new \Exception('路网数据有误', ERR_ROAD_MAPINFO_FAILED);
        }

        $juncMovements = $this->waymap_model->getFlowMovement($cityId, $junctionIdList[sizeof($junctionIdList) - 1], 'all', 1);
        //下游路口
        $down_road_degree = [];
        foreach ($juncMovements as $item) {
            if ($item['junction_id'] == $junctionIdList[sizeof($junctionIdList) - 1] &&
                $item['upstream_junction_id'] == $junctionIdList[sizeof($junctionIdList) - 2]) {

                $absDiff = abs(floatval($item['in_degree']) - floatval($item['out_degree']));
                if($absDiff>180){
                    $absDiff = 360-$absDiff;
                }
                //print_r($absDiff);
                //print_r($item['downstream_junction_id']);
                $down_road_degree[$item['downstream_junction_id']] = $absDiff;
            }
        }
        //exit;
        if (!empty($down_road_degree)) {
            asort($down_road_degree);
            array_push($junctionIdList, (string)key($down_road_degree));    //尾部插入
        } else {
            //throw new \Exception('路网数据有误', ERR_ROAD_MAPINFO_FAILED);
        }

        return $junctionIdList;
    }

    /**
     * 获取全城全部路口详情
     *
     * @param $params ['city_id'] int 城市ID
     *
     * @return array
     */
    public function getAllRoadDetail($params)
    {
        $cityId = $params['city_id'];
        $show_type = $params['show_type'];
        $force = $params['force'] ?? 0 ;
        $pre_key = $show_type ? 'Road_extend_' : 'Road_';

        $select = 'id, road_id, logic_junction_ids, road_name, road_direction';

        $roadList = $this->road_model->getRoadsByCityId($cityId, $select);
        $results = [];
        foreach ($roadList as $item) {
            $roadId = $item['road_id'];
            $res = $this->redis_model->getData($pre_key . $roadId);
            if ($force) {
                $res = [];
            }
            if (!$res) {
                $data = [
                    'city_id' => $cityId,
                    'road_id' => $roadId,
                    'show_type' => $show_type,
                ];
                try {
                    $res = $this->getRoadDetail($data);
                } catch (\Exception $e) {
                    $res = [];
                }
                // 将数据刷新到 Redis
                $this->redis_model->setEx($pre_key . $roadId, json_encode($res), 86400);
            } else {
                $res = json_decode($res, true);
            }
            $res['road'] = $item;
            $res['road_id'] = $item['id'];
            $results[] = $res;
        }

        return $results;
    }

    /**
     * 获取干线详情
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getRoadDetail($params)
    {
        $cityId = $params['city_id'];
        $roadId = $params['road_id'];
        $showType = $params['show_type'];
        $roadInfo = $this->road_model->getRoadByRoadId($roadId, 'logic_junction_ids');
        if($showType){
            $junctionIdList = $this->getPathHeadTailJunction(["city_id"=>$cityId,
                "junction_ids"=>$roadInfo['logic_junction_ids']]);
        }else{
            $junctionIdList = explode(",",$roadInfo['logic_junction_ids']);
        }
        $maxWaymapVersion = $this->waymap_model->getLastMapVersion();
        $res = $this->waymap_model->getConnectPath($cityId, $maxWaymapVersion, $junctionIdList);
        if (!$res || empty($res['junctions_info']) || empty($res['forward_path_flows']) || empty($res['backward_path_flows'])) {
            throw new \Exception('路网数据有误', ERR_ROAD_MAPINFO_FAILED);
        }

        $junctionList = $res['junctions_info'];
        $forwardPathFlows = $res['forward_path_flows'];
        $backwardPathFlows = $res['backward_path_flows'];

        $getStartEndJunctionIdKeyCallback = function ($item) {
            return $item['start_junc_id'] . '-' . $item['end_junc_id'];
        };
        $getEndStartJunctionIdKeyCallback = function ($item) {
            return $item['end_junc_id'] . '-' . $item['start_junc_id'];
        };
        $getFirstItemCallback = function ($item) {
            return is_array($item) ? current($item) : $item;
        };

        $forwardPathFlowsCollection = Collection::make($forwardPathFlows)->groupBy($getStartEndJunctionIdKeyCallback, $getFirstItemCallback);
        $backwardPathFlowsCollection = Collection::make($backwardPathFlows)->groupBy($getEndStartJunctionIdKeyCallback, $getFirstItemCallback);

        $roadInfo = [];

        foreach ($forwardPathFlowsCollection as $key => $item) {

            $forwardGeo = $this->waymap_model->getLinksGeoInfos($item['path_links'], $maxWaymapVersion);

            $roadInfo[$key] = [
                'start_junc_id' => $item['start_junc_id'],
                'end_junc_id' => $item['end_junc_id'],
                'links' => $item['path_links'],
                'forward_geo' => $forwardGeo,
            ];
        }

        foreach ($backwardPathFlowsCollection as $key => $item) {
            if (isset($roadInfo[$key])) {
                $roadInfo[$key]['reverse_geo'] = [];
            }
        }

        $junctionCollection = Collection::make($junctionList);

        $junctionsInfo = [];

        foreach ($junctionIdList as $id) {
            $junctionsInfo[$id] = [
                'logic_junction_id' => $id,
                'junction_name' => $junctionList[$id]['name'] ?? '未知路口',
                'lng' => $junctionList[$id]['lng'] ?? 0,
                'lat' => $junctionList[$id]['lat'] ?? 0,
                'node_ids' => $junctionList[$id]['node_ids'] ?? [],
            ];
        }

        $center = [
            'lng' => $junctionCollection->avg('lng'),
            'lat' => $junctionCollection->avg('lat'),
        ];

        $junctionsInfo = array_values($junctionsInfo);

        return [
            'road_info' => array_values($roadInfo),
            'junctions_info' => $junctionsInfo,
            'center' => $center,
            'map_version' => $maxWaymapVersion,
        ];
    }

    /**
     * 干线评估
     *
     * @param $params
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function comparison($params)
    {
        $roadId = $params['road_id'];
        $cityId = $params['city_id'];
        $direction = $params['direction'];
        $quotaKey = $params['quota_key'];
        $baseStartDate = $params['base_start_date'];
        $baseEndDate = $params['base_end_date'];
        $evaluateStartDate = $params['evaluate_start_date'];
        $evaluateEndDate = $params['evaluate_end_date'];

        // 指标算法映射
        $methods = [
            'stop_time_cycle' => 'round(sum(stop_time_cycle), 2) as stop_time_cycle',
            'stop_delay' => 'round(sum(stop_delay), 2) as stop_delay',
            'speed' => 'round(avg(speed) * 3.6, 2) as speed',
            'time' => '',
        ];

        $nameMaps = $this->config->item('road_map');

        // 获取指标单位
        $units = array_column($this->config->item('road'), 'unit', 'key');

        // 如果指标不在映射数组中，返回空数组
        if (!isset($methods[$quotaKey])) {
            throw new \Exception('查找的指标不存在');
        }

        // 获取干线路口数据
        $select = 'road_name, logic_junction_ids';
        $roadInfo = $this->road_model->getRoadByRoadId($roadId, $select);

        // 获取干线数据失败
        if (!$roadInfo) {
            throw new \Exception('获取干线信息失败');
        }

        $roadName = $roadInfo['road_name'];

        $junctionIdList = explode(',', $roadInfo['logic_junction_ids']);

        // 最新路网版本
        $newMapVersion = $this->waymap_model->getLastMapVersion();

        // 调用路网接口获取干线路口信息
        $res = $this->waymap_model->getConnectPath($cityId, $newMapVersion, $junctionIdList);

        // 根据参数决定获取数据指定方向的 flow 集合
        $dataKey = $direction == 1 ? 'forward_path_flows' : 'backward_path_flows';

        // 路网数据没有该方向
        if (!isset($res[$dataKey])) {
            throw new \Exception('该方向没有数据');
        }

        // 生成指定时间范围内的 基准日期集合数组，评估日期集合数组
        $baseDates = dateRange($baseStartDate, $baseEndDate);
        $evaluateDates = dateRange($evaluateStartDate, $evaluateEndDate);

        // 生成 00:00 - 23:30 间的 粒度为 30 分钟的时间集合数组
        $hours = hourRange();

        $flowIdList = array_map(function ($item) {
            return $item['logic_flow']['logic_flow_id'] ?? '';
        }, $res[$dataKey]);

        $flowLength = [];
        // 在查找通行时间时，构建 CASE THEN SQL 语句
        if ($quotaKey == 'time') {

            $timeCaseWhen = 'round(sum(CASE WHEN speed = 0 THEN 0 ';

            // 获取每个 flow 的长度
            foreach ($res[$dataKey] as $item) {
                if (isset($item['logic_flow']['logic_flow_id']) && $item['logic_flow']['logic_flow_id'] != '') {

                    $timeCaseWhen .= 'WHEN logic_flow_id = \'' . $item['logic_flow']['logic_flow_id']
                        . '\' THEN ' . $item['length'] . ' / speed ';
                    $flowLength[] = [
                        'flowid' => $item['logic_flow']['logic_flow_id'],
                        'length' => $item['length'],
                    ];
                }
            }

            $timeCaseWhen .= 'ELSE 0 END), 2) time';

            $methods['time'] = $timeCaseWhen;
        }

        $select = 'date, hour, ' . $methods[$quotaKey];
        $dates = array_merge($baseDates, $evaluateDates);

        // 获取数据源集合
        $result = $this->flowDurationV6_model->getJunctionByCityId($dates, $flowIdList, $cityId, $quotaKey, $flowLength, $select);
        if (!$result) {
            return [];
        }

        // 将数据按照 日期（基准 和 评估）进行分组的键名函数
        $baseOrEvaluateCallback = function ($item) use ($baseDates) {
            return in_array($item['date'], $baseDates) ? 'base' : 'evaluate';
        };

        // 数据分组后，将每组数据进行处理的函数
        $groupByItemFormatCallback = function ($item) use ($hours) {
            $hourToNull  = array_combine($hours, array_fill(0, 48, null));
            $item        = array_column($item, 'quota_value', 'hour');
            $hourToValue = array_merge($hourToNull, $item);

            $result = [];

            foreach ($hourToValue as $hour => $value) {
                $result[] = [$hour, $value];
            }

            return $result;
        };

        // 数据处理
        $result = Collection::make($result)
            ->groupBy([$baseOrEvaluateCallback, 'date'], $groupByItemFormatCallback)
            ->get();

        $result['info'] = [
            'road_name' => $roadName,
            'quota_name' => $nameMaps[$quotaKey] ?? '',
            'quota_unit' => $units[$quotaKey] ?? '',
            'direction' => $direction == 1 ? '正向' : '反向',
            'base_time' => [$baseStartDate, $baseEndDate],
            'evaluate_time' => [$evaluateStartDate, $evaluateEndDate],
        ];

        $jsonResult = json_encode($result);

        $downloadId = md5($jsonResult);

        $result['info']['download_id'] = $downloadId;

        $this->redis_model->setComparisonDownloadData($downloadId, $result);

        return $result;
    }

    /**
     * 获取评估数据下载链接
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function downloadEvaluateData($params)
    {
        $downloadId = $params['download_id'];

        $key = $this->config->item('quota_evaluate_key_prefix') . $downloadId;

        if (!$this->redis_model->getData($key)) {
            throw new \Exception('请先评估再下载', ERR_PARAMETERS);
        }

        return [
            'download_url' => $this->config->item('road_download_url_prefix') . $params['download_id'],
        ];
    }

    /**
     * @param $params
     *
     * @throws \Exception
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function download($params)
    {
        $downloadId = $params['download_id'];

        $data = $this->redis_model->getComparisonDownloadData($downloadId);

        if (!$data) {
            throw new \Exception('请先评估再下载', ERR_PARAMETERS);
        }

        $excelStyle = $this->config->item('excel_style');

        $fileName = "{$data['info']['road_name']}_" . date('Ymd');

        $objPHPExcel = new \PHPExcel();
        $objSheet = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('数据');

        $detailParams = [
            ['指标名', $data['info']['quota_name']],
            ['方向', $data['info']['direction'] ?? ''],
            ['基准时间', implode(' ~ ', $data['info']['base_time'])],
            ['评估时间', implode(' ~ ', $data['info']['evaluate_time'])],
            ['指标单位', $data['info']['quota_unit']],
        ];

        $objSheet->mergeCells('A1:F1');
        $objSheet->setCellValue('A1', $fileName);
        $objSheet->fromArray($detailParams, null, 'A4');

        $objSheet->getStyle('A1')->applyFromArray($excelStyle['title']);
        $rows_idx = count($detailParams) + 3;
        $objSheet->getStyle("A4:A{$rows_idx}")->getFont()->setSize(12)->setBold(true);

        $line = 6 + count($detailParams);

        if (!empty($data['base'])) {

            $table = getExcelArray($data['base']);

            $objSheet->fromArray($table, null, 'A' . $line);

            $rows_cnt = count($table);
            $cols_cnt = count($table[0]) - 1;
            $rows_index = $rows_cnt + $line - 1;

            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $rows_index)->applyFromArray($excelStyle['content']);
            $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($excelStyle['header']);
            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $line)->applyFromArray($excelStyle['header']);

            $line += ($rows_cnt + 2);
        }

        if (!empty($data['evaluate'])) {

            $table = getExcelArray($data['evaluate']);

            $objSheet->fromArray($table, null, 'A' . $line);

            $rows_cnt = count($table);
            $cols_cnt = count($table[0]) - 1;
            $rows_index = $rows_cnt + $line - 1;
            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $rows_index)->applyFromArray($excelStyle['content']);
            $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($excelStyle['header']);
            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $line)->applyFromArray($excelStyle['header']);
        }

        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        header('Content-Type: application/x-xls;');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $fileName . '.xls');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: 0'); // Date in the past
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        ob_end_clean();
        $objWriter->save('php://output');
        exit();
    }
}
