<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/24
 * Time: 上午11:35
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class OverviewService  alarmanalysis_model
 * @package Services
 * @property \Alarmanalysis_model   $alarmanalysis_model
 * @property \Realtime_model      $realtime_model
 */
class OverviewService extends BaseService
{
    protected $helperService;

    /**
     * OverviewService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->helperService = new HelperService();

        $this->load->model('redis_model');
        $this->load->model('waymap_model');
        $this->load->model('realtime_model');
        $this->load->model('alarmanalysis_model');

        $this->config->load('realtime_conf');
    }

    /**
     * 路口概况
     *
     * @param $params
     * @param $userPerm 用户权限点
     *
     * @return array
     * @throws \Exception
     */
    public function junctionSurvey($params,$userPerm=[])
    {
        $hour = $this->helperService->getLastestHour($params['city_id']);
        if(!empty($userPerm['group_id'])){
            $redisKey = 'new_its_usergroup_realtime_pretreat_junction_survey_'. $userPerm['group_id'] . '_' . $params['city_id'] . '_' . $params['date'] . '_' . $hour;
        }else{
            $redisKey = 'new_its_realtime_pretreat_junction_survey_' . $params['city_id'] . '_' . $params['date'] . '_' . $hour;
        }
        $data = $this->redis_model->getData($redisKey);
        if (empty($data)) {
            return [
                'junction_total'   => 0,
                'alarm_total'      => 0,
                'congestion_total' => 0,
            ];
        }

        $result = json_decode($data, true);

        return $result;
    }

    /**
     * 路口列表
     *
     * @param $params
     *
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    public function junctionsList($params,$userPerm=[])
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        $hour = $this->helperService->getLastestHour($cityId);

        $data = $this->redis_model->getRealtimePretreatJunctionList($cityId, $date, $hour);

        if(!empty($userPerm)){
            $cityIds = !empty($userPerm['city_id']) ? $userPerm['city_id'] : [];
            $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
            if(in_array($cityId,$cityIds)){
                $junctionIds = [];
            }
            if(!in_array($cityId,$cityIds) && empty($junctionIds)){
                return [];
            }
        }

        $params['date'] = $params['date'] ?? date('Y-m-d');
        $params['pagesize'] = $params['pagesize'] ?? 20;

        $delayList = $this->stopDelayTopList($params,$this->userPerm);
        $newDelayList = [];
        foreach ($delayList as $item){
            $newDelayList[$item["logic_junction_id"]] = $item["stop_delay"];
        }

        $cycleList = $this->stopTimeCycleTopList($params,$this->userPerm);
        $newCycleList = [];
        foreach ($cycleList as $item){
            if(isset($newCycleList[$item["logic_junction_id"]])){
                if($item["stop_time_cycle"]>$newCycleList[$item["logic_junction_id"]]){
                    $newCycleList[$item["logic_junction_id"]] = $item["stop_time_cycle"];
                }
            }else{
                $newCycleList[$item["logic_junction_id"]] = $item["stop_time_cycle"];
            }
        }

        if(!empty($data)){
            foreach ($data["dataList"] as $key=>$item){
                if(!empty($junctionIds) && !in_array($item["jid"],$junctionIds)){
                    unset($data["dataList"][$key]);
                    continue;
                }
                if(isset($newDelayList[$item["jid"]])){
                    $data["dataList"][$key]["quota"]["stop_delay"]["value"] = $newDelayList[$item["jid"]];
                }
                if(isset($newCycleList[$item["jid"]])){
                    $data["dataList"][$key]["quota"]["stop_time_cycle"]["value"] = $newCycleList[$item["jid"]];
                }
            }
            $data["dataList"] = array_values($data["dataList"]);
            $lngs = array_filter(array_column($data["dataList"], 'lng'));
            $lats = array_filter(array_column($data["dataList"], 'lat'));
            $data['center']['lng'] = count($lngs) == 0 ? 0 : (array_sum($lngs) / count($lngs));
            $data['center']['lat'] = count($lats) == 0 ? 0 : (array_sum($lats) / count($lats));
        }
        return $data ? $data : [];
    }

    /**
     * 运行情况 概览页 平均延误
     *
     * @param $params['city_id'] int    Y 城市ID
     * @param $params['date']    string N 日期 yyyy-mm-dd
     * @param $userPerm    array N 权限数据
     * @return array
     * @throws \Exception
     */
    public function operationCondition($params,$userPerm)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        $result = $this->redis_model->getRealtimeAvgStopDelay($cityId, $date, $userPerm);
        if (empty($result)) {
            return (object)[];
        }
        $resultTmp = array_values($result);
        $result = [];
        foreach ($resultTmp as $key=>$item){
            if(!empty($item['avg_stop_delay']) && $item['avg_stop_delay']>0){
                $result[] = $item;
            }
        }

        $realTimeQuota = $this->config->item('real_time_quota');

        $formatQuotaRoundHour = function ($v) use ($realTimeQuota) {
            return [
                //取得配置方法
                $realTimeQuota['stop_delay']['round']($v['avg_stop_delay']),
                substr($v['hour'], 0, 5),
            ];
        };

        $ext = [];
        $skipMap = [];
        $findSkip = function ($carry, $item) use (&$ext,&$skipMap) {
            //$carry是上一个元素值
            $now = strtotime($item[1] ?? '00:00');
            $pretime = strtotime($carry[1] ?? '00:00');

            $nowValue = floatval($item[0]);
            $preValue = floatval($carry[0]);
            if ($now - $pretime >= 30 * 60) {
                $rangeHours = range($pretime + 5 * 60, $now - 5 * 60, 5 * 60);
                $ext = array_merge($ext, $rangeHours);

                $rangemap = array_flip($rangeHours);
                $rangeavg = ($nowValue-$preValue)/count($rangemap);
                foreach ($rangemap as $rk=>$rv){
                    $rangemap[date('H:i', $rk)] = $preValue+$rv*$rangeavg;
                }
                $skipMap = array_merge($skipMap, $rangemap);
            }
            return $item;
        };

        $resultCollection = Collection::make($result)->map($formatQuotaRoundHour);

        $info = [
            'value' => $resultCollection->avg(0, $realTimeQuota['stop_delay']['round']),
            'unit' => $realTimeQuota['stop_delay']['unit'],
        ];

        $resultCollection->reduce($findSkip, [0,'00:00']);

        $result = $resultCollection->merge(array_map(function ($v) use ($skipMap) {
            return [$skipMap[date('H:i', $v)], date('H:i', $v)];
        }, $ext))->sortBy(1);

        //过滤重复数据
        $newResult = [];
        foreach ($result as $item){
            if(!isset($newResult[$item[1]])){
                $newResult[$item[1]] = $item;
            }
        }
        return [
            'dataList' => array_values($newResult),
            'info' => $info,
        ];
    }

    /**
     * 拥堵概览
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 当前时间点 格式：H:i:s 例：09:10:00
     * @param $userPerm array N 用户权限点
     * @return array
     * @throws \Exception
     */
    public function getCongestionInfo($params, $userPerm=[])
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        $hour = $this->helperService->getLastestHour($cityId);

        // 路口概况redis key
        if(!empty($userPerm['group_id'])){
            $redisKey = 'new_its_usergroup_realtime_pretreat_junction_survey_'. $userPerm['group_id'] . '_' . $cityId . '_' . $date . '_' . $hour;
        }else{
            $redisKey = 'new_its_realtime_pretreat_junction_survey_' . $cityId . '_' . $date . '_' . $hour;
        }

        // 获取路口概况信息
        $res = $this->redis_model->getData($redisKey);
        if (!$res) {
            return [];
        }
        $res = json_decode($res, true);

        $result = [];

        // 路口总数
        $junctionTotal = $res['junction_total'] ?? 0;
        if ($junctionTotal < 1) {
            return [];
        }
        // 缓存数
        $ambleNum = $res['amble_total'] ?? 0;
        // 拥堵数
        $congestionNum = $res['congestion_total'] ?? 0;

        $congestionInfo = [
            // 畅通
            1 => $junctionTotal - ($ambleNum + $congestionNum),

            // 缓行
            2 => $ambleNum,

            // 拥堵
            3 => $congestionNum,
        ];

        // 路口状态配置
        $junctionStatusConf = $this->config->item('junction_status');


        $result['count'] = [];
        $result['ratio'] = [];
        foreach ($junctionStatusConf as $k => $v) {
            $result['count'][$k] = [
                'cate' => $v['name'],
                'num' => $congestionInfo[$k],
            ];

            $result['ratio'][$k] = [
                'cate' => $v['name'],
                'ratio' => round(($congestionInfo[$k] / $junctionTotal) * 100) . '%',
            ];
        }

        $result['count'] = array_values($result['count']);
        $result['ratio'] = array_values($result['ratio']);

        return $result;
    }

    /**
     * 获取 token
     *
     * @return array
     */
    public function getToken()
    {
        $token = md5(time() . rand(1, 10000) * rand(1, 10000));

        $this->redis_model->setData('Token_' . $token, $token);
        $this->redis_model->setExpire('Token_' . $token, 60 * 30);

        return [
            $token,
        ];
    }

    /**
     * 验证 token
     *
     * @param $params
     *
     * @return array
     */
    public function verifyToken($params)
    {
        $token = 'Token_' . $params['tokenval'];

        $res = $this->redis_model->verifyToken($token);

        return [
            'verify' => $res,
        ];
    }

    /**
     * 获取当前服务器时间
     *
     * @return array
     */
    public function getNowDate()
    {
        $weekArray = [
            '日', '一', '二', '三', '四', '五', '六',
        ];

        $time = time();

        return [
            'date' => date('Y-m-d', $time),
            'time' => date('H:i:s', $time),
            'week' => '星期' . $weekArray[date('w', $time)],
        ];
    }


    /**
     * 获取路口的停车延误时间曲线
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function junctionStopDelayCurve($params)
    {
        $cityId   = $params['city_id'];
        $junction_id     = $params['junction_id'];
        $date     = $params['date'];
        while(1){
            $result = $this->realtime_model->getJunctionAvgStopDelayList($cityId, $junction_id, $date);
            print_r($result);
            exit;
        }
    }

    /**
     * 获取停车延误TOP20
     * @param $params['city_id']  int    Y 城市ID
     * @param $params['date']     string N 日期 yyyy-mm-dd
     * @param $params['pagesize'] int    N 获取数量
     * @param $params['junction_ids'] string    N 路口id以逗号间隔
     * @param $userPerm array    N 路口id以逗号间隔
     * @return array
     * @throws \Exception
     */
    public function stopDelayTopList($params,$userPerm=[])
    {
        $cityId   = $params['city_id'];
        $date     = $params['date'];
        $pagesize = $params['pagesize'];
        $junctionIds = !empty($params['junction_ids']) ? explode(",",$params['junction_ids']) : [];  //array
        if(!empty($userPerm)){
            $cityIds = !empty($userPerm['city_id']) ? $userPerm['city_id'] : [];
            $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
            if(in_array($cityId,$cityIds)){
                $junctionIds = [];
            }
            if(!in_array($cityId,$cityIds) && empty($junctionIds)){
                return [];
            }
        }
        $hour = $this->helperService->getIndexLastestHour($cityId);
        $esRes = $this->realtime_model->getTopStopDelay($cityId, $date, $hour, $pagesize, $junctionIds);
        $result = array_column($esRes, 'quotaMap');
        if (empty($result)) {
            return [];
        }

        $ids = implode(',', array_unique(array_column($result, 'junctionId')));

        if(!empty($ids)){
            $junctionIdNames = $this->waymap_model->getJunctionInfo($ids);
            $junctionIdNames = array_column($junctionIdNames, 'name', 'logic_junction_id');
        }
        $realTimeQuota = $this->config->item('real_time_quota');

        $result = array_map(function ($item) use ($junctionIdNames, $realTimeQuota, $hour) {
            return [
                'time' => $hour,
                'logic_junction_id' => $item['junctionId'],
                'junction_name' => $junctionIdNames[$item['junctionId']] ?? '未知路口',
                'stop_delay' => $realTimeQuota['stop_delay']['round']($item['weight_avg']),
                'quota_unit' => $realTimeQuota['stop_delay']['unit'],
            ];
        }, $result);

        return $result;
    }

    /**
     * 获取停车次数TOP20
     * @param $params['city_id']  int    Y 城市ID
     * @param $params['date']     string N 日期 yyyy-mm-dd
     * @param $params['pagesize'] int    N 获取数量
     * @param $userPerm array    N 用户权限
     * @return array
     * @throws \Exception
     */
    public function stopTimeCycleTopList($params,$userPerm=[])
    {
        $cityId   = $params['city_id'];
        $date     = $params['date'];
        $pagesize = $params['pagesize'];

        $junctionIds = !empty($params['junction_ids']) ? explode(",",$params['junction_ids']) : [];  //array
        if(!empty($userPerm)){
            $cityIds = !empty($userPerm['city_id']) ? $userPerm['city_id'] : [];
            $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
            if(in_array($cityId,$cityIds)){
                $junctionIds = [];
            }
            if(!in_array($cityId,$cityIds) && empty($junctionIds)){
                return [];
            }
        }

        $hour = $this->helperService->getIndexLastestHour($cityId);
        $result = $this->realtime_model->getTopCycleTime($cityId, $date, $hour, $pagesize, $junctionIds);
        if (empty($result)) {
            return [];
        }

        $ids = implode(',', array_unique(array_column($result, 'junctionId')));

        $junctionIdNames = [];
        $flowsInfo = [];
        if(!empty($ids)){
            $junctionIdNames = $this->waymap_model->getJunctionInfo($ids);
            $junctionIdNames = array_column($junctionIdNames, 'name', 'logic_junction_id');
            $flowsInfo = $this->waymap_model->getFlowsInfo($ids);
        }

        $realTimeQuota = $this->config->item('real_time_quota');

        $result = array_map(function ($item) use ($junctionIdNames, $realTimeQuota, $flowsInfo) {
            return [
                'time'              => date('H:i:s', strtotime($item['dayTime'])),
                'logic_junction_id' => $item['junctionId'],
                'junction_name'     => $junctionIdNames[$item['junctionId']] ?? '未知路口',
                'logic_flow_id'     => $item['movementId'],
                'flow_name'         => $flowsInfo[$item['junctionId']][$item['movementId']] ?? '未知方向',
                'stop_time_cycle'   => $realTimeQuota['stop_time_cycle']['round']($item['avgStopNumUp']),
                'quota_unit'        => $realTimeQuota['stop_time_cycle']['unit'],
            ];
        }, $result);

        return $result;
    }

    /**
     * 获取今日报警预览
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 时间 HH:ii:ss
     * @param $junctionIds          array  N 路口Ids
     * @return array
     * @throws \Exception
     */
    public function todayAlarmInfoByJunctionIds($params,$junctionIds)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        $chunkJunctionIds = array_chunk($junctionIds,1000);

        $res = [];
        $total = 0;
        foreach ($chunkJunctionIds as $Jids){
            // 组织ES所需JSON
            $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

            // where city_id
            $json .= '{"match":{"city_id":{"query":' . $cityId . ',"type":"phrase"}}}';

            // where date
            $json .= ',{"match":{"date":{"query":"' . trim($date) . '","type":"phrase"}}}';

            /* where junctionId in*/
            if(!empty($Jids)){
                $json .= ',{"bool":{"should":[';

                for($x=0;$x<count($Jids);$x++){
                    $json .= '{"match":{"logic_junction_id":{"query":"' . $Jids[$x] . '","type":"phrase"}}}';
                    if ($x<(count($Jids)-1)) {
                        $json .= ',';
                    }
                }
                $json .= ']}}';
            }

            $json .= ']}}}},"_source":{"includes":["COUNT"],"excludes":[]},"aggregations":{"type":{"terms":{"field":"type","size":200},"aggregations":{"num":{"cardinality":{"field":"logic_junction_id","precision_threshold":40000}}}}}}';
            $esRes = $this->alarmanalysis_model->search($json);

            if (!$esRes) {
                return [];
            }

            // 格式
            foreach ($esRes['aggregations']['type']['buckets'] as $k=>$v) {
                if(!empty($res[$v['key']])){
                    $res[$v['key']]+=$v['num']['value'];
                }else{
                    $res[$v['key']] = $v['num']['value'];
                }
                $total += $v['num']['value'];
            }
        }

        // 报警类别配置
        $alarmCate = $this->config->item('alarm_category');

        $result = [];
        foreach ($alarmCate as $k=>$v) {
            $num = $res[$v['key']] ?? 0;
            $result['count'][$k] = [
                'cate' => $v['name'],
                'num'  => $num,
            ];

            $result['ratio'][$k] = [
                'cate' => $v['name'],
                'ratio' => ($total >= 1) ? round(($num / $total) * 100) . '%' : '0%',
            ];
        }

        $result['count'] = array_values($result['count']);
        $result['ratio'] = array_values($result['ratio']);

        return $result;
    }

    /**
     * 获取今日报警预览
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 时间 HH:ii:ss
     * @param $userPerm array N 用户权限
     * @return array
     * @throws \Exception
     */
    public function todayAlarmInfo($params,$userPerm=[])
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        $cityIds = !empty($userPerm['city_id']) ? $userPerm['city_id'] : [];
        $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
        if(in_array($cityId,$cityIds)){
            $junctionIds = [];
        }
        //用户登陆,但没有数据权限
        if(!empty($userPerm) && !in_array($cityId,$cityIds) && empty($junctionIds)){
            return [];
        }

        if(!empty($junctionIds)){
            return $this->todayAlarmInfoByJunctionIds($params,$junctionIds);
        }

        // 组织ES所需JSON
        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . $cityId . ',"type":"phrase"}}}';

        // where date
        $json .= ',{"match":{"date":{"query":"' . trim($date) . '","type":"phrase"}}}';

        $json .= ']}}}},"_source":{"includes":["COUNT"],"excludes":[]},"aggregations":{"type":{"terms":{"field":"type","size":200},"aggregations":{"num":{"cardinality":{"field":"logic_junction_id","precision_threshold":40000}}}}}}';
        $esRes = $this->alarmanalysis_model->search($json);
        if (empty($esRes['aggregations']['type']['buckets']) || !$esRes) {
            return [];
        }

        // 报警类别配置
        $alarmCate = $this->config->item('alarm_category');

        $res = [];
        $total = 0;
        // 格式
        foreach ($esRes['aggregations']['type']['buckets'] as $k=>$v) {
            if (!in_array($v['key'], array_keys($alarmCate))) {
                continue;
            }
            $res[$v['key']] = $v['num']['value'];
            $total += $v['num']['value'];
        }

        $result = [];
        $totalRatio = 0;
        foreach ($alarmCate as $k=>$v) {
            $num = $res[$v['key']] ?? 0;
            $result['count'][$k] = [
                'cate' => $v['name'],
                'num'  => $num,
            ];
            $ratio = ($total >= 1) ? round(($num / $total) * 100) : 0;
            // 保证加起来正好等于100%
            if ($k == 3) {
                $ratio = 100 - $totalRatio;
            }
            $totalRatio += $ratio;
            $result['ratio'][$k] = [
                'cate' => $v['name'],
                'ratio' => $ratio . '%',
            ];
        }

        $result['count'] = array_values($result['count']);
        $result['ratio'] = array_values($result['ratio']);

        return $result;
    }


    /**
     * 获取七日报警变化通过路口
     * 规则：取当前日期前六天的报警路口数+当天到现在时刻的报警路口数
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 时间 HH:ii:ss
     * @param $junctionIds          array  N 路口ids
     * @throws Exception
     * @return array
     */
    public function sevenDaysAlarmChangeByJunctionId($params,$junctionIds)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        // 七日日期
        $sevenDates = [];

        // 前6天时间戳作为开始时间
        $startDate = strtotime($date . '-6 day');
        // 当前日期时间戳作为结束时间
        $endDate = strtotime($date);
        $chunkJunctionIds = array_chunk($junctionIds,1000);

        //分组获取权限数据
        $tmpRs = [];
        foreach ($chunkJunctionIds as $jIds){
            // 组织DSL所需json
            $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

            // where city_id
            $json .= '{"match":{"city_id":{"query":' . $cityId . ',"type":"phrase"}}}';

            /* where date in*/
            $json .= ',{"bool":{"should":[';
            for ($i = $startDate; $i <= $endDate; $i += 24 * 3600) {
                $json .= '{"match":{"date":{"query":"' . date('Y-m-d', $i) . '","type":"phrase"}}}';
                if ($i < $endDate) {
                    $json .= ',';
                }
            }
            $json .= ']}}';

            /* where junctionId in*/
            if(!empty($jIds)){
                $json .= ',{"bool":{"should":[';

                for($x=0;$x<count($jIds);$x++){
                    $json .= '{"match":{"logic_junction_id":{"query":"' . $jIds[$x] . '","type":"phrase"}}}';
                    if ($x<(count($jIds)-1)) {
                        $json .= ',';
                    }
                }
                $json .= ']}}';
            }

            $json .= ']}}}},"_source":{"includes":["COUNT"],"excludes":[]},"sort":[{"date":{"order":"asc"}}],"aggregations":{"date":{"terms":{"field":"date","size":200,"order":{"_term":"asc"}},"aggregations":{"num":{"cardinality":{"field":"logic_junction_id","precision_threshold":40000}}}}}}';
            $data = $this->alarmanalysis_model->search($json);
            if (!$data || empty($data['aggregations']['date']['buckets'])) {
                return [];
            }

            foreach ($data['aggregations']['date']['buckets'] as $k=>$v) {
                $dataStr = date('Y-m-d', $v['key'] / 1000);
                if(!empty($tmpRs[$dataStr])){
                    $tmpRs[$dataStr]+=$v['num']['value'];
                }else{
                    $tmpRs[$dataStr] = $v['num']['value'];
                }
            }
        }

        $result['dataList'] = [];
        foreach ($tmpRs as $date=>$value){
            $result['dataList'][] = [
                "date"=>$date,
                "value"=>$value,
            ];
        }
        return $result;
    }

    /**
     * 获取七日报警变化
     * 规则：取当前日期前六天的报警路口数+当天到现在时刻的报警路口数
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 时间 HH:ii:ss
     * @param $userPerm             array N 用户权限
     * @throws Exception
     * @return array
     */
    public function sevenDaysAlarmChange($params,$userPerm=[])
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        //权限逻辑开始
        $cityIds = !empty($userPerm['city_id']) ? $userPerm['city_id'] : [];
        $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
        if(in_array($cityId,$cityIds)){
            $junctionIds = [];
        }
        //权限不为空 且 无城市权限 且 无路口数据
        if(!empty($userPerm) && !in_array($cityId,$cityIds) && empty($junctionIds)){
            return [];
        }
        if(!empty($junctionIds)){
            return $this->sevenDaysAlarmChangeByJunctionId($params,$junctionIds);
        }
        //权限逻辑结束

        // 七日日期
        $sevenDates = [];

        // 前6天时间戳作为开始时间
        $startDate = strtotime($date . '-6 day');
        // 当前日期时间戳作为结束时间
        $endDate = strtotime($date);

        // 组织DSL所需json
        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . $cityId . ',"type":"phrase"}}}';

        /* where date in*/
        $json .= ',{"bool":{"should":[';
        for ($i = $startDate; $i <= $endDate; $i += 24 * 3600) {
            $json .= '{"match":{"date":{"query":"' . date('Y-m-d', $i) . '","type":"phrase"}}}';
            if ($i < $endDate) {
                $json .= ',';
            }
        }
        $json .= ']}}';
        $json .= ']}}}},"_source":{"includes":["COUNT"],"excludes":[]},"sort":[{"date":{"order":"asc"}}],"aggregations":{"date":{"terms":{"field":"date","size":200,"order":{"_term":"asc"}},"aggregations":{"num":{"cardinality":{"field":"logic_junction_id","precision_threshold":40000}}}}}}';
        $data = $this->alarmanalysis_model->search($json);
        if (!$data || empty($data['aggregations']['date']['buckets'])) {
            return [];
        }

        $result['dataList'] = [];
        foreach ($data['aggregations']['date']['buckets'] as $k=>$v) {
            $result['dataList'][$k] = [
                'date'  => date('Y-m-d', $v['key'] / 1000),
                'value' => $v['num']['value'],
            ];
        }
        return $result;
    }

    /**
     * 获取实时报警信息
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 当前时间点 格式：H:i:s 例：09:10:00
     * @return array
     * @throws \Exception
     */
    public function realTimeAlarmList($params,$userPerm=[])
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        if(!empty($userPerm)){
            $cityIds = !empty($userPerm['city_id']) ? $userPerm['city_id'] : [];
            $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
            if(in_array($cityId,$cityIds)){
                $junctionIds = [];
            }
            if(!in_array($cityId,$cityIds) && empty($junctionIds)){
                return [];
            }
        }

        $res = $this->alarmanalysis_model->getRealTimeAlarmsInfo($cityId, $date);
        if (!$res || empty($res)) {
            return [];
        }
        $result = [];

        // 需要获取路口name的路口ID串
        $alarmJunctonIdArr = array_unique(array_column($res, 'logic_junction_id'));
        asort($alarmJunctonIdArr);
        $ids = implode(',', $alarmJunctonIdArr);

        // 获取路口相位信息
        try {
            $flowsInfo = $this->waymap_model->getFlowsInfo($ids,true);
        } catch (\Exception $e) {
            $flowsInfo = [];
        }

        // 获取路口信息
        $junctionsInfo = $this->waymap_model->getAllCityJunctions($cityId, 0);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');
        $alarmCate = $this->config->item('flow_alarm_category');

        foreach ($res as $k => $val) {
            $durationTime = round((strtotime($val['last_time']) - strtotime($val['start_time'])) / 60, 2);
            if ($durationTime < 1) {
                $durationTime = 2;
            }

            //无权限路口数据跳过
            if(!empty($junctionIds) && !in_array($val["logic_junction_id"],$junctionIds)){
                continue;
            }
            if (!empty($junctionIdName[$val['logic_junction_id']])
                && !empty($flowsInfo[$val['logic_junction_id']][$val['logic_flow_id']])) {
                $result['dataList'][$k] = [
                    'start_time' => date('H:i', strtotime($val['start_time'])),
                    'full_time' => date('Y-m-d H:i:s', strtotime($val['start_time'])),
                    'duration_time' => $durationTime,
                    'logic_junction_id' => $val['logic_junction_id'],
                    'junction_name' => $junctionIdName[$val['logic_junction_id']] ?? '',
                    'logic_flow_id' => $val['logic_flow_id'],
                    'flow_name' => $flowsInfo[$val['logic_junction_id']][$val['logic_flow_id']] ?? '',
                    'alarm_comment' => $alarmCate[$val['type']]['name'] ?? '',
                    'alarm_key' => $val['type'],
                    'order' => $alarmCate[$val['type']]['order'] ?? 0,
                ];
            }
        }

        if (empty($result['dataList'])) {
            return [];
        }
        usort($result['dataList'], function($a, $b) {
            if ($a['order'] != $b['order']) {
                return ($a['order'] < $b['order']) ? 1 : -1;
            }
            return ($a['duration_time'] < $b['duration_time']) ? 1 : -1;
        });
        $result['dataList'] = array_values($result['dataList']);

        return $result;
    }

    /**
     * 处理从数据库中取出的原始数据并返回
     *
     * @param $cityId
     * @param $result
     * @param $realTimeAlarmsInfo
     *
     * @return array
     * @throws \Exception
     */
    public function getJunctionListResult($cityId, $result, $realTimeAlarmsInfo)
    {
        //获取路口信息的自定义返回格式
        $junctionsInfo = $this->waymap_model->getAllCityJunctions($cityId, 0);
        $junctionsInfo = array_column($junctionsInfo, null, 'logic_junction_id');

        //获取需要报警的全部路口ID
        $ids = implode(',', array_column($realTimeAlarmsInfo, 'logic_junction_id'));

        //获取需要报警的全部路口的全部方向的信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($ids);

        //数组初步处理，去除无用数据
        $result = array_map(function ($item) use ($flowsInfo, $realTimeAlarmsInfo) {
            return [
                'logic_junction_id' => $item['logic_junction_id'],
                'quota' => $this->getRawQuotaInfo($item),
                'alarm_info' => $this->getRawAlarmInfo($item, $flowsInfo, $realTimeAlarmsInfo),
            ];
        }, $result);

        //数组按照 logic_junction_id 进行合并
        $temp = [];
        foreach ($result as $item) {
            $temp[$item['logic_junction_id']] = isset($temp[$item['logic_junction_id']]) ?
                $this->mergeFlowInfo($temp[$item['logic_junction_id']], $item) :
                $item;
        };

        //处理数据内容格式
        $temp = array_map(function ($item) use ($junctionsInfo) {
            return [
                'jid' => $item['logic_junction_id'],
                'name' => $junctionsInfo[$item['logic_junction_id']]['name'] ?? '',
                'lng' => $junctionsInfo[$item['logic_junction_id']]['lng'] ?? '',
                'lat' => $junctionsInfo[$item['logic_junction_id']]['lat'] ?? '',
                'quota' => ($quota = $this->getFinalQuotaInfo($item)),
                'alarm' => $this->getFinalAlarmInfo($item),
                'status' => $this->getJunctionStatus($quota),
            ];
        }, $temp);

        $lngs = array_filter(array_column($temp, 'lng'));
        $lats = array_filter(array_column($temp, 'lat'));

        $center['lng'] = count($lngs) == 0 ? 0 : (array_sum($lngs) / count($lngs));
        $center['lat'] = count($lats) == 0 ? 0 : (array_sum($lats) / count($lats));

        return [
            'dataList' => array_values($temp),
            'center' => $center,
        ];
    }

    /**
     * 获取原始指标信息
     *
     * @param $item
     *
     * @return array
     */
    private function getRawQuotaInfo($item)
    {
        return [
            'stop_delay_weight' => $item['stop_delay'] * $item['traj_count'],
            'stop_time_cycle' => $item['stop_time_cycle'],
            'traj_count' => $item['traj_count'],
        ];
    }

    /**
     * 获取原始报警信息
     *
     * @param $item
     * @param $flowsInfo
     * @param $realTimeAlarmsInfo
     *
     * @return array
     */
    private function getRawAlarmInfo($item, $flowsInfo, $realTimeAlarmsInfo)
    {
        $alarmCategory = $this->config->item('alarm_category');

        $result = [];

        if (isset($flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']])) {
            foreach ($alarmCategory as $key => $value) {
                if (array_key_exists($item['logic_flow_id'] . $key, $realTimeAlarmsInfo)) {
                    $result[] = $flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']] .
                        '-' . $value['name'];
                }
            }
        }

        return $result;
    }

    /**
     * 数据处理，多个 flow 记录合并到其对应 junction
     *
     * @param $target
     * @param $item
     *
     * @return mixed
     */
    private function mergeFlowInfo($target, $item)
    {
        //合并属性 停车延误加权求和，停车时间求最大，权值求和
        $target['quota']['stop_delay_weight'] += $item['quota']['stop_delay_weight'];
        $target['quota']['stop_time_cycle']   = max($target['quota']['stop_time_cycle'], $item['quota']['stop_time_cycle']);
        $target['quota']['traj_count']        += $item['quota']['traj_count'];

        if (isset($target['alarm_info'])) {
            //合并报警信息
            $target['alarm_info'] = array_merge($target['alarm_info'], $item['alarm_info']) ?? [];
        }

        return $target;
    }

    /**
     * 获取最终指标信息
     *
     * @param $item
     *
     * @return array
     */
    private function getFinalQuotaInfo($item)
    {
        //实时指标配置文件
        $realTimeQuota = $this->config->item('real_time_quota');

        return [
            'stop_delay' => [
                'name' => '平均延误',
                'value' => $realTimeQuota['stop_delay']['round']($item['quota']['stop_delay_weight'] / $item['quota']['traj_count']),
                'unit' => $realTimeQuota['stop_delay']['unit'],
            ],
            'stop_time_cycle' => [
                'name' => '最大停车次数',
                'value' => $realTimeQuota['stop_time_cycle']['round']($item['quota']['stop_time_cycle']),
                'unit' => $realTimeQuota['stop_time_cycle']['unit'],
            ],
        ];
    }

    /**
     * 获取最终报警信息
     *
     * @param $item
     *
     * @return array
     */
    private function getFinalAlarmInfo($item)
    {
        return [
            'is' => (int)!empty($item['alarm_info']),
            'comment' => $item['alarm_info'],
        ];
    }

    /**
     * 获取当前路口的状态
     *
     * @param $quota
     *
     * @return mixed
     */
    private function getJunctionStatus($quota)
    {
        $junctionStatus = $this->config->item('junction_status');

        $junctionStatusFormula = $this->config->item('junction_status_formula');

        return $junctionStatus[$junctionStatusFormula($quota['stop_delay']['value'])];
    }
}
