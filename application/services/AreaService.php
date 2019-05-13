<?php
/**
 * 信控平台 - 区域相关接口
 *
 * User: lichaoxi_i@didichuxing.com
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class AreaService
 * @package Services
 * @property \Area_model $area_model
 */
class AreaService extends BaseService
{
    /**
     * AreaService constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('waymap_model');
        $this->load->model('area_model');
        $this->load->model('redis_model');

        $this->load->config('nconf');
        $this->load->config('realtime_conf');
        $this->load->config('evaluate_conf');
    }

    /**
     * 获取区域列表
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function getList($params)
    {

        $cityId = $params['city_id'];

        $areaList = $this->area_model->getAreasByCityId($cityId);

        return [
            'list' => $areaList,
        ];
    }

    /**
     * 添加区域
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function addArea($params)
    {
        $cityId      = $params['city_id'];
        $areaName    = $params['area_name'];
        $junctionIds = $params['junction_ids'];

        $data = [
            'area_name' => $areaName,
            'city_id' => $cityId,
        ];

        if (!$this->area_model->areaNameIsUnique($areaName, $cityId)) {
            throw new \Exception('区域名称 ' . $areaName . ' 已经存在', ERR_DATABASE);
        }

        // 创建区域
        $areaId = $this->area_model->insertArea($data);

        // 创建区域路口关联
        $failCount = $this->area_model->insertAreaJunctions($areaId, $junctionIds);

        if ($failCount === 0) {
            throw new \Exception('插入区域路口失败', ERR_PARAMETERS);
        }

        return $areaId;
    }

    /**
     * 更新区域
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function updateArea($params)
    {
        $areaId      = $params['area_id'];
        $areaName    = $params['area_name'];
        $junctionIds = $params['junction_ids'];

        // 获取区域信息
        $areaInfo = $this->area_model->getAreaByAreaId($areaId);

        if (!$areaInfo || empty($areaInfo)) {
            throw new \Exception('目标区域不存在', ERR_PARAMETERS);
        }

        $areaId = $areaInfo['id'];
        $cityId      = $areaInfo['city_id'];

        $data = [
            'area_name' => $areaName,
        ];

        if (!$this->area_model->areaNameIsUnique($areaName, $cityId, $areaId)) {
            throw new \Exception('区域名称 ' . $areaName . ' 已经存在', ERR_DATABASE);
        }

        // 更新区域信息
        $res = $this->area_model->updateArea($areaId, $data);

        if (!$res) {
            throw new \Exception('更新区域失败', ERR_PARAMETERS);
        }

        $areaJunctionList = $this->area_model->getAreaJunctions($areaId, 'junction_id');

        $oldJunctionIds = array_column($areaJunctionList, 'junction_id');
        $newJunctionIds = $junctionIds;

        $shouldDeleted = array_diff($oldJunctionIds, $newJunctionIds);
        $shouldCreated = array_diff($newJunctionIds, $oldJunctionIds);

        $this->area_model->insertAreaJunctions($areaId, $shouldCreated);
        $this->area_model->deleteAreaJunctions($areaId, $shouldDeleted);

        return $areaId;
    }

    /**
     * 删除区域
     *
     * @param $params
     *
     * @return mixed
     */
    public function deleteArea($params)
    {
        $areaId = $params['area_id'];

        return $this->area_model->deleteArea($areaId);
    }

    /**
     * 获取指定区域详情
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getAreaDetail($params)
    {
        //$cityId = $params['city_id'];
        $areaId = $params['area_id'];

        $areaInfo = $this->area_model->getAreaByAreaId($areaId);

        if (!$areaInfo) {
            throw new \Exception(' 目标区域不存在', ERR_PARAMETERS);
        }

        $areaJunctionList = $this->area_model->getAreaJunctions($areaId);

        $areaJunctionCollection = Collection::make($areaJunctionList);

        // 取小数后6位
        $round = function ($item) {
            return round($item, 6);
        };

        $junctionIds = $areaJunctionCollection->implode('junction_id', ',');

        $junctionInfoList = $this->waymap_model->getJunctionInfo($junctionIds);

        $junctionInfoCollection = Collection::make($junctionInfoList);

        $centerLat = $junctionInfoCollection->avg('lat', $round);
        $centerLng = $junctionInfoCollection->avg('lng', $round);

        return [
            'center_lat' => $centerLat,
            'center_lng' => $centerLng,
            'area_id' => $areaId,
            'area_name' => $areaInfo['area_name'] ?? '',
            'junction_list' => $junctionInfoList,
        ];
    }

    /**
     * 获取城市全部区域详情
     * @param $params['city_id'] int 城市ID
     * @return array
     * @throws \Exception
     */
    public function getCityAreaDetail($params)
    {
        $cityId = $params['city_id'];

        // 获取城市全部区域信息
        $areaList       = $this->area_model->getAreasByCityId($cityId, 'id, area_name');
        if (empty($areaList)) {
            return (object)[];
        }
        $areaCollection = Collection::make($areaList);

        // 获取区域ID
        $areaIdList = $areaCollection->column('id')->get();

        // 获取 ID 和 名称的映射
        $areaIdToNameList = $areaCollection->column('area_name', 'id')->get();

        // 获取全部区域路口映射
        $areaJunctionList       = $this->area_model->getAreaJunctionsByAreaIds($areaIdList);
        $areaJunctionCollection = Collection::make($areaJunctionList);

        // 从路网获取路口信息
        $junctionIds    = $areaJunctionCollection->implode('junction_id', ',');
        $junctionList   = $this->waymap_model->getJunctionInfo($junctionIds);
        $junctionIdList = array_column($junctionList, null, 'logic_junction_id');

        $areaIdJunctionList = $areaJunctionCollection
            ->groupBy('area_id', function ($item) {
                return array_column($item, 'junction_id');
            })->krsort();

        $results = [];

        foreach ($areaIdJunctionList as $areaId => $junctionIds) {

            $junctionCollection = Collection::make([]);

            foreach ($junctionIds as $id) {
                $junctionCollection[] = $junctionIdList[$id] ?? '';
            }

            $results[] = [
                'area_id' => $areaId,
                'area_name' => $areaIdToNameList[$areaId] ?? '',
                'center_lat' => $junctionCollection->avg('lat'),
                'center_lng' => $junctionCollection->avg('lng'),
                'junction_list' => $junctionCollection,
            ];
        }

        return $results;
    }

    /**
     * 获取区域指标
     *
     * @return mixed
     */
    public function getQuotas()
    {
        return $this->config->item('area');
    }

    /**
     * 区域指标评估
     *
     * @param $params
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function comparison($params)
    {
        $areaId            = $params['area_id'];
        $quotaKey          = $params['quota_key'];
        $cityId            = $params['city_id'];
        $baseStartDate     = $params['base_start_date'];
        $baseEndDate       = $params['base_end_date'];
        $evaluateStartDate = $params['evaluate_start_date'];
        $evaluateEndDate   = $params['evaluate_end_date'];

        // 指标算法映射
        $methods = [
            'speed' => 'round(avg(speed) * 3.6, 2) as speed',
            'stop_delay' => 'round(avg(stop_delay), 2) as stop_delay',
        ];

        // 名称配置
        $nameMaps = $this->config->item('area_map');

        // 获取指标单位
        $units = array_column($this->config->item('area'), 'unit', 'key');

        // 指标不存在与映射数组中
        if (!isset($methods[$quotaKey])) {
            throw new \Exception('指标不存在', ERR_PARAMETERS);
        }

        $areaInfo = $this->area_model->getAreaByAreaId($areaId);

        if (!$areaInfo) {
            throw new \Exception('该区域已被删除', ERR_PARAMETERS);
        }

        // 获取该区域全部路口ID
        $junctionList = $this->area_model->getJunctionsByAreaId($areaId, 'junction_id');

        // 数据获取失败 或者 数据为空
        if (!$junctionList || empty($junctionList)) {
            throw new \Exception('路口数据获取失败', ERR_PARAMETERS);
        }

        $junctionCollection = Collection::make($junctionList);

        $junctionIds = $junctionCollection->column('junction_id')->get();

        // 基准、评估时间范围
        $baseDates     = dateRange($baseStartDate, $baseEndDate);
        $evaluateDates = dateRange($evaluateStartDate, $evaluateEndDate);

        // 生成 00:00 - 23:30 间的 粒度为 30 分钟的时间集合数组
        $hours = hourRange();

        // 获取数据
        $select = 'date, hour, ' . $methods[$quotaKey];
        $dates  = array_merge($baseDates, $evaluateDates);

        $resultList = $this->area_model->getJunctionByCityId($dates, $junctionIds, $hours, $cityId, $select);
        if (!$resultList || empty($resultList)) {
            return  [];
        }

        $resultCollection = Collection::make($resultList);

        // 将数据按照 日期（基准 和 评估）进行分组的键名函数
        $baseOrEvaluateCallback = function ($item) use ($baseDates) {
            return in_array($item['date'], $baseDates) ? 'base' : 'evaluate';
        };

        // 数据分组后，将每组数据进行处理的函数
        $groupByItemFormatCallback = function ($item) use ($params, $hours) {
            $hourToNull  = array_combine($hours, array_fill(0, 48, null));
            $item        = array_column($item, $params['quota_key'], 'hour');
            $hourToValue = array_merge($hourToNull, $item);

            $result = [];

            foreach ($hourToValue as $hour => $value) {
                $result[] = [$hour, $value];
            }

            return $result;
        };

        // 数据处理
        $result = $resultCollection
            ->groupBy([$baseOrEvaluateCallback, 'date'], $groupByItemFormatCallback)
            ->get();

        $result['info'] = [
            'area_name' => $areaInfo['area_name'],
            'quota_name' => $nameMaps[$quotaKey] ?? '',
            'quota_unit' => $units[$quotaKey] ?? '',
            'base_time' => [$baseStartDate, $baseEndDate],
            'evaluate_time' => [$evaluateStartDate, $evaluateEndDate],
        ];

        // 构建 Redis Key
        $downloadId                    = md5(json_encode($result));
        $result['info']['download_id'] = $downloadId;

        $this->redis_model->setComparisonDownloadData($downloadId, $result);

        return $result;
    }

    /**
     * 获取区域评估下载地址
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function downloadEvaluataData($params)
    {
        $downloadId = $params['download_id'];

        $key = $this->config->item('quota_evaluate_key_prefix') . $downloadId;

        if (!$this->redis_model->getData($key)) {
            throw new \Exception('请先评估再下载', ERR_PARAMETERS);
        }

        return [
            'download_url' => $this->config->item('area_download_url_prefix') . $params['download_id'],
        ];

    }

    /**
     * 评估数据文件下载
     *
     * @param $params
     *
     * @throws \Exception
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function download($params)
    {
        $downloadId = $params['download_id'];

        $excelStyle = $this->config->item('excel_style');

        $data = $this->redis_model->getComparisonDownloadData($downloadId);

        if (!$data) {
            throw new \Exception('请先评估再下载', ERR_PARAMETERS);
        }

        $fileName = "{$data['info']['area_name']}_" . date('Ymd');

        $objPHPExcel = new \PHPExcel();
        $objSheet    = $objPHPExcel->getActiveSheet();
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

            $rows_cnt   = count($table);
            $cols_cnt   = count($table[0]) - 1;
            $rows_index = $rows_cnt + $line - 1;

            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $rows_index)->applyFromArray($excelStyle['content']);
            $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($excelStyle['header']);
            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $line)->applyFromArray($excelStyle['header']);

            $line += ($rows_cnt + 2);
        }

        if (!empty($data['evaluate'])) {

            $table = getExcelArray($data['evaluate']);

            $objSheet->fromArray($table, null, 'A' . $line);

            $rows_cnt   = count($table);
            $cols_cnt   = count($table[0]) - 1;
            $rows_index = $rows_cnt + $line - 1;
            //$objSheet->getStyle("A≈{$line}:" . intToChr($cols_cnt) . $rows_index)->applyFromArray($excelStyle['content']);
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