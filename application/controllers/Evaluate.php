<?php
/***************************************************************
# 评估类
# user:ningxiangbing@didichuxing.com
# date:2018-07-25
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Evaluate extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('evaluate_model');
        $this->load->model('redis_model');
        $this->load->config('realtime_conf');
    }

    /**
     * 获取全城路口列表
     * @param city_id    interger Y 城市ID
     * @return json
     */
    public function getCityJunctionList()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->evaluate_model->getCityJunctionList($data);

        $this->response($data);
    }

    /**
     * 获取指标列表
     * @param city_id    interger Y 城市ID
     * @return json
     */
    public function getQuotaList()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->evaluate_model->getQuotaList($data);

        $this->response($data);
    }

    /**
     * 获取相位（方向）列表
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @return json
     */
    public function getDirectionList()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        if(!isset($params['junction_id']) || empty(trim($params['junction_id']))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of junction_id is empty.';
            return;
        }

        $data['junction_id'] = $params['junction_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->evaluate_model->getDirectionList($data);

        $this->response($data);
    }

    /**
     * 获取路口指标排序列表
     * @param city_id     interger Y 城市ID
     * @param quota_key   string   Y 指标KEY
     * @param date        string   N 日期 格式：Y-m-d 默认当前日期
     * @param time_point  string   N 时间 格式：H:i:s 默认当前时间
     * @return json
     */
    public function getJunctionQuotaSortList()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'   => 'min:1',
                'quota_key' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (!array_key_exists($params['quota_key'], $this->config->item('real_time_quota'))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '指标 ' . html_escape($params['quota_key']) . ' 不存在！';
            return;
        }

        $data = [
            'city_id'    => intval($params['city_id']),
            'quota_key'  => strip_tags(trim($params['quota_key'])),
            'date'       => date('Y-m-d'),
            'time_point' => date('H:i:s'),
        ];

        if (!empty($params['date'])) {
            $data['date'] = date('Y-m-d', strtotime(strip_tags(trim($params['date']))));
        }

        if (!empty($params['time_point'])) {
            $data['time_point'] = date('H:i:s', strtotime(strip_tags(trim($params['time_point']))));
        }

        $result = $this->evaluate_model->getJunctionQuotaSortList($data);

        return $this->response($result);
    }

    /**
     * 获取指标趋势图
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @param quota_key   string   Y 指标KEY
     * @param flow_id     string   Y 相位ID
     * @param date        string   N 日期 格式：Y-m-d 默认当前日期
     * @param time_point  string   N 时间 格式：H:i:s 默认当前时间
     * @return json
     */
    public function getQuotaTrend()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'     => 'min:1',
                'junction_id' => 'nullunable',
                'quota_key'   => 'nullunable',
                'flow_id'     => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (!array_key_exists($params['quota_key'], $this->config->item('real_time_quota'))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '指标 ' . html_escape($params['quota_key']) . ' 不存在！';
            return;
        }

        $data = [
            'city_id'     => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'quota_key'   => strip_tags(trim($params['quota_key'])),
            'flow_id'     => strip_tags(trim($params['flow_id'])),
            'date'        => date('Y-m-d'),
            'time_point'  => date('H:i:s'),
        ];

        if (!empty($params['date'])) {
            $data['date'] = date('Y-m-d', strtotime(strip_tags(trim($params['date']))));
        }

        if (!empty($params['time_point'])) {
            $data['time_point'] = date('H:i:s', strtotime(strip_tags(trim($params['time_point']))));
        }

        $result = $this->evaluate_model->getQuotaTrend($data);

        return $this->response($result);
    }

    /**
     * 获取路口地图数据
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @return json
     */
    public function getJunctionMapData()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'     => 'min:1',
                'junction_id' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = [
            'city_id'     => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
        ];

        $result = $this->evaluate_model->getJunctionMapData($data);

        return $this->response($result);
    }

    /**
     * 指标评估对比
     * @param city_id         interger Y 城市ID
     * @param junction_id     string   Y 路口ID
     * @param quota_key       string   Y 指标KEY
     * @param flow_id         string   Y 相位ID
     * @param base_start_time string   N 基准开始时间 格式：yyyy-mm-dd hh:ii:ss
     * 例：2018-08-06 00:00:00 默认：上一周工作日开始时间（上周一 yyyy-mm-dd 00:00:00）
     * @param base_end_time   string   N 基准结束时间 格式：yyyy-mm-dd hh:ii:ss
     * 例：2018-08-07 23:59:59 默认：上一周工作日结束时间（上周五 yyyy-mm-dd 23:59:59）
     * @param evaluate_time   string   N 评估时间 有可能会有多个评估时间段，固使用json格式的字符串
     * evaluate_time 格式：
     * [
     *     {
     *         "start_time": "2018-08-01 00:00:00", // 开始时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-06 00:00:00
     *         "end_time": "2018-08-07 23:59:59"    // 结束时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-07 23:59:59
     *     },
     *     ......
     * ]
     * @return json
     */
    public function quotaEvaluateCompare()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'     => 'min:1',
                'junction_id' => 'nullunable',
                'quota_key'   => 'nullunable',
                'flow_id'     => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = [
            'city_id'     => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'quota_key'   => strip_tags(trim($params['quota_key'])),
            'flow_id'     => strip_tags(trim($params['flow_id'])),
        ];

        /**
         * 如果基准时间没有传，则默认：上周工作日
         * 如果评估时间没有传，则默认：本周工作日
         */
        if (empty($params['base_start_time'])) {
            // 上周一作为开始时间 Y-m-d H:i:s
            $baseStartTime = strtotime('monday last week');
        } else {
            $baseStartTime = strtotime($params['base_start_time']);
        }

        if (empty($params['base_end_time'])) {
            // 上周五作为结束时间 本周减去2天减1秒
            $baseEndTime = strtotime('monday this week') - 2 * 24 * 3600 - 1;
        } else {
            $baseEndTime = strtotime($params['base_end_time']);
        }

        // 用于返回
        $data['base_time_start_end'] = [
            'start' => date('Y-m-d H:i:s', $baseStartTime),
            'end'   => date('Y-m-d H:i:s', $baseEndTime),
        ];

        // 计算基准时间段具体每天日期
        for ($i = $baseStartTime; $i < $baseEndTime; $i += 24 * 3600) {
            $data['base_time'][] = $i;
        }

        if (empty($params['evaluate_time'])) {
            // 开始时间 本周一开始时间
            $startTime = strtotime('monday this week');

            // 当前星期几 如果星期一，结束时间要到当前时间 如果大于星期一，结束时间要前一天 如果是周日则向前推两天
            $week = date('w');
            if ($week == 0) { // 周日
                $endTime = strtotime(date('Y-m-d') . '-2 days') + 24 * 3600 - 1;
            } else if ($week == 1) { // 周一
                $endTime = time();
            } else {
                $endTime = strtotime(date('Y-m-d') . '-1 days') + 24 * 3600 - 1;
            }

            $params['evaluate_time'][] = [
                'start_time' => $startTime,
                'end_time'   => $endTime,
            ];
        } else {
            // 解析json
            $params['evaluate_time'] = json_decode($params['evaluate_time'], true);
            if (json_last_error() != JSON_ERROR_NONE) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数 evaluate_time 非json格式的文本！';
                return;
            }

            foreach ($params['evaluate_time'] as $k=>$v) {
                $params['evaluate_time'][$k] = [
                    'start_time' => strtotime($v['start_time']),
                    'end_time' => strtotime($v['end_time']),
                ];
            }
        }

        // 用于返回
        $data['evaluate_time_start_end'] = [];

        // 处理评估时间，计算各评估时间具体日期
        foreach ($params['evaluate_time'] as $k=>$v) {
            for ($i = $v['start_time']; $i <= $v['end_time']; $i += 24 * 3600) {
                $data['evaluate_time'][$k][$i] = $i;
            }
            $data['evaluate_time_start_end'][$k] = [
                'start' => date('Y-m-d H:i:s', $v['start_time']),
                'end'   => date('Y-m-d H:i:s', $v['end_time']),
            ];
        }

        $result = $this->evaluate_model->quotaEvaluateCompare($data);

        return $this->response($result);
    }

    /**
     * 下载评估对比数据
     * @param
     * @return json
     */
    public function downloadEvaluateData()
    {
        $params = $this->input->get();

        if(!isset($params['download_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = "download_id 的值不能为空";
            return;
        }

        $key = $this->config->item('quota_evaluate_key_prefix') . $params['download_id'];

        if(!$this->redis_model->getData($key)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = "请先评估再下载";
            return;
        }

        $data = [
            'download_url' => '/evaluate/download?download_id='. $params['download_id']
        ];

        $this->response($data);
    }

    public function download()
    {
        $params = $this->input->get();

        if(!isset($params['download_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = "download_id 的值不能为空";
            return;
        }

        $key = $this->config->item('quota_evaluate_key_prefix') . $params['download_id'];

        //if(!($data = $this->redis_model->getData($key))) {
        //    $this->errno = ERR_PARAMETERS;
        //    $this->errmsg = "请先评估再下载";
        //    return;
        //}
        $data = '{"base":{"2018-08-01":[[189,"00:00:00"],[262,"00:30:00"],[265,"01:00:00"],[63,"01:30:00"],[227,"02:00:00"],[138,"02:30:00"],[103,"03:00:00"],[57,"03:30:00"],[150,"04:00:00"],[245,"04:30:00"],[157,"05:00:00"],[162,"05:30:00"],[63,"06:00:00"],[225,"06:30:00"],[286,"07:00:00"],[277,"07:30:00"],[109,"08:00:00"],[99,"08:30:00"],[62,"09:00:00"],[155,"09:30:00"],[133,"10:00:00"],[102,"10:30:00"],[129,"11:00:00"],[248,"11:30:00"],[237,"12:00:00"],[193,"12:30:00"],[96,"13:00:00"],[258,"13:30:00"],[218,"14:00:00"],[146,"14:30:00"],[36,"15:00:00"],[153,"15:30:00"],[159,"16:00:00"],[177,"16:30:00"],[184,"17:00:00"],[220,"17:30:00"],[129,"18:00:00"],[177,"18:30:00"],[70,"19:00:00"],[175,"19:30:00"],[82,"20:00:00"],[125,"20:30:00"],[255,"21:00:00"],[262,"21:30:00"],[28,"22:00:00"],[145,"22:30:00"],[153,"23:00:00"],[246,"23:30:00"]],"2018-08-02":[[223,"00:00:00"],[186,"00:30:00"],[81,"01:00:00"],[223,"01:30:00"],[177,"02:00:00"],[78,"02:30:00"],[31,"03:00:00"],[77,"03:30:00"],[98,"04:00:00"],[273,"04:30:00"],[131,"05:00:00"],[186,"05:30:00"],[134,"06:00:00"],[161,"06:30:00"],[178,"07:00:00"],[182,"07:30:00"],[219,"08:00:00"],[194,"08:30:00"],[146,"09:00:00"],[192,"09:30:00"],[205,"10:00:00"],[141,"10:30:00"],[165,"11:00:00"],[82,"11:30:00"],[93,"12:00:00"],[49,"12:30:00"],[130,"13:00:00"],[102,"13:30:00"],[252,"14:00:00"],[116,"14:30:00"],[90,"15:00:00"],[216,"15:30:00"],[122,"16:00:00"],[89,"16:30:00"],[132,"17:00:00"],[144,"17:30:00"],[118,"18:00:00"],[207,"18:30:00"],[214,"19:00:00"],[215,"19:30:00"],[124,"20:00:00"],[126,"20:30:00"],[176,"21:00:00"],[170,"21:30:00"],[163,"22:00:00"],[98,"22:30:00"],[208,"23:00:00"],[265,"23:30:00"]],"2018-08-03":[[136,"00:00:00"],[171,"00:30:00"],[176,"01:00:00"],[207,"01:30:00"],[241,"02:00:00"],[269,"02:30:00"],[89,"03:00:00"],[201,"03:30:00"],[117,"04:00:00"],[159,"04:30:00"],[201,"05:00:00"],[81,"05:30:00"],[243,"06:00:00"],[187,"06:30:00"],[209,"07:00:00"],[200,"07:30:00"],[109,"08:00:00"],[102,"08:30:00"],[207,"09:00:00"],[166,"09:30:00"],[125,"10:00:00"],[237,"10:30:00"],[265,"11:00:00"],[53,"11:30:00"],[106,"12:00:00"],[49,"12:30:00"],[43,"13:00:00"],[177,"13:30:00"],[189,"14:00:00"],[239,"14:30:00"],[35,"15:00:00"],[101,"15:30:00"],[142,"16:00:00"],[158,"16:30:00"],[279,"17:00:00"],[221,"17:30:00"],[151,"18:00:00"],[227,"18:30:00"],[125,"19:00:00"],[193,"19:30:00"],[201,"20:00:00"],[115,"20:30:00"],[192,"21:00:00"],[147,"21:30:00"],[176,"22:00:00"],[179,"22:30:00"],[223,"23:00:00"],[189,"23:30:00"]],"2018-07-30":[[210,"00:00:00"],[118,"00:30:00"],[95,"01:00:00"],[86,"01:30:00"],[110,"02:00:00"],[95,"02:30:00"],[206,"03:00:00"],[179,"03:30:00"],[145,"04:00:00"],[142,"04:30:00"],[209,"05:00:00"],[170,"05:30:00"],[206,"06:00:00"],[160,"06:30:00"],[119,"07:00:00"],[86,"07:30:00"],[181,"08:00:00"],[168,"08:30:00"],[179,"09:00:00"],[75,"09:30:00"],[111,"10:00:00"],[220,"10:30:00"],[277,"11:00:00"],[176,"11:30:00"],[116,"12:00:00"],[156,"12:30:00"],[163,"13:00:00"],[218,"13:30:00"],[283,"14:00:00"],[197,"14:30:00"],[155,"15:00:00"],[224,"15:30:00"],[134,"16:00:00"],[140,"16:30:00"],[78,"17:00:00"],[164,"17:30:00"],[35,"18:00:00"],[155,"18:30:00"],[204,"19:00:00"],[117,"19:30:00"],[184,"20:00:00"],[115,"20:30:00"],[195,"21:00:00"],[96,"21:30:00"],[210,"22:00:00"],[168,"22:30:00"],[147,"23:00:00"],[68,"23:30:00"]],"2018-07-31":[[165,"00:00:00"],[133,"00:30:00"],[213,"01:00:00"],[105,"01:30:00"],[162,"02:00:00"],[155,"02:30:00"],[191,"03:00:00"],[189,"03:30:00"],[131,"04:00:00"],[128,"04:30:00"],[228,"05:00:00"],[165,"05:30:00"],[240,"06:00:00"],[241,"06:30:00"],[193,"07:00:00"],[126,"07:30:00"],[192,"08:00:00"],[77,"08:30:00"],[187,"09:00:00"],[142,"09:30:00"],[47,"10:00:00"],[163,"10:30:00"],[225,"11:00:00"],[170,"11:30:00"],[181,"12:00:00"],[222,"12:30:00"],[144,"13:00:00"],[167,"13:30:00"],[237,"14:00:00"],[152,"14:30:00"],[83,"15:00:00"],[195,"15:30:00"],[135,"16:00:00"],[162,"16:30:00"],[226,"17:00:00"],[183,"17:30:00"],[141,"18:00:00"],[160,"18:30:00"],[162,"19:00:00"],[185,"19:30:00"],[118,"20:00:00"],[206,"20:30:00"],[220,"21:00:00"],[84,"21:30:00"],[223,"22:00:00"],[106,"22:30:00"],[96,"23:00:00"],[200,"23:30:00"]]},"evaluate":{"1":{"2018-08-01":[[189,"00:00:00"],[262,"00:30:00"],[265,"01:00:00"],[63,"01:30:00"],[227,"02:00:00"],[138,"02:30:00"],[103,"03:00:00"],[57,"03:30:00"],[150,"04:00:00"],[245,"04:30:00"],[157,"05:00:00"],[162,"05:30:00"],[63,"06:00:00"],[225,"06:30:00"],[286,"07:00:00"],[277,"07:30:00"],[109,"08:00:00"],[99,"08:30:00"],[62,"09:00:00"],[155,"09:30:00"],[133,"10:00:00"],[102,"10:30:00"],[129,"11:00:00"],[248,"11:30:00"],[237,"12:00:00"],[193,"12:30:00"],[96,"13:00:00"],[258,"13:30:00"],[218,"14:00:00"],[146,"14:30:00"],[36,"15:00:00"],[153,"15:30:00"],[159,"16:00:00"],[177,"16:30:00"],[184,"17:00:00"],[220,"17:30:00"],[129,"18:00:00"],[177,"18:30:00"],[70,"19:00:00"],[175,"19:30:00"],[82,"20:00:00"],[125,"20:30:00"],[255,"21:00:00"],[262,"21:30:00"],[28,"22:00:00"],[145,"22:30:00"],[153,"23:00:00"],[246,"23:30:00"]],"2018-08-02":[[223,"00:00:00"],[186,"00:30:00"],[81,"01:00:00"],[223,"01:30:00"],[177,"02:00:00"],[78,"02:30:00"],[31,"03:00:00"],[77,"03:30:00"],[98,"04:00:00"],[273,"04:30:00"],[131,"05:00:00"],[186,"05:30:00"],[134,"06:00:00"],[161,"06:30:00"],[178,"07:00:00"],[182,"07:30:00"],[219,"08:00:00"],[194,"08:30:00"],[146,"09:00:00"],[192,"09:30:00"],[205,"10:00:00"],[141,"10:30:00"],[165,"11:00:00"],[82,"11:30:00"],[93,"12:00:00"],[49,"12:30:00"],[130,"13:00:00"],[102,"13:30:00"],[252,"14:00:00"],[116,"14:30:00"],[90,"15:00:00"],[216,"15:30:00"],[122,"16:00:00"],[89,"16:30:00"],[132,"17:00:00"],[144,"17:30:00"],[118,"18:00:00"],[207,"18:30:00"],[214,"19:00:00"],[215,"19:30:00"],[124,"20:00:00"],[126,"20:30:00"],[176,"21:00:00"],[170,"21:30:00"],[163,"22:00:00"],[98,"22:30:00"],[208,"23:00:00"],[265,"23:30:00"]]}},"average":{"base":[[185,"00:00:00"],[174,"00:30:00"],[166,"01:00:00"],[137,"01:30:00"],[183,"02:00:00"],[147,"02:30:00"],[124,"03:00:00"],[140,"03:30:00"],[128,"04:00:00"],[189,"04:30:00"],[185,"05:00:00"],[153,"05:30:00"],[177,"06:00:00"],[195,"06:30:00"],[197,"07:00:00"],[174,"07:30:00"],[162,"08:00:00"],[128,"08:30:00"],[156,"09:00:00"],[146,"09:30:00"],[124,"10:00:00"],[172,"10:30:00"],[212,"11:00:00"],[146,"11:30:00"],[146,"12:00:00"],[134,"12:30:00"],[115,"13:00:00"],[184,"13:30:00"],[236,"14:00:00"],[170,"14:30:00"],[80,"15:00:00"],[178,"15:30:00"],[138,"16:00:00"],[145,"16:30:00"],[180,"17:00:00"],[186,"17:30:00"],[115,"18:00:00"],[185,"18:30:00"],[155,"19:00:00"],[177,"19:30:00"],[142,"20:00:00"],[137,"20:30:00"],[207,"21:00:00"],[152,"21:30:00"],[160,"22:00:00"],[139,"22:30:00"],[165,"23:00:00"],[193,"23:30:00"]],"evaluate":{"1":[[206,"00:00:00"],[224,"00:30:00"],[173,"01:00:00"],[143,"01:30:00"],[202,"02:00:00"],[108,"02:30:00"],[67,"03:00:00"],[67,"03:30:00"],[124,"04:00:00"],[259,"04:30:00"],[144,"05:00:00"],[174,"05:30:00"],[98,"06:00:00"],[193,"06:30:00"],[232,"07:00:00"],[229,"07:30:00"],[164,"08:00:00"],[146,"08:30:00"],[104,"09:00:00"],[173,"09:30:00"],[169,"10:00:00"],[121,"10:30:00"],[147,"11:00:00"],[165,"11:30:00"],[165,"12:00:00"],[121,"12:30:00"],[113,"13:00:00"],[180,"13:30:00"],[235,"14:00:00"],[131,"14:30:00"],[63,"15:00:00"],[185,"15:30:00"],[140,"16:00:00"],[133,"16:30:00"],[158,"17:00:00"],[182,"17:30:00"],[124,"18:00:00"],[192,"18:30:00"],[142,"19:00:00"],[195,"19:30:00"],[103,"20:00:00"],[125,"20:30:00"],[216,"21:00:00"],[216,"21:30:00"],[95,"22:00:00"],[121,"22:30:00"],[180,"23:00:00"],[255,"23:30:00"]]}},"info":{"junction_name":"\u5f20\u5e84\u8def_\u6bb5\u5174\u4e1c\u8def","quota_name":"\u6392\u961f\u957f\u961f","quota_unit":"\u7c73","base_time":{"start":"2018-07-30 00:00:00","end":"2018-08-03 23:59:59"},"evaluate_time":[{"start":"2018-08-01 00:00:00","end":"2018-08-02 23:59:59"}],"direction":"\u6240\u6709\u65b9\u5411","download_id":"c0ca5e5c0470b77f8450c26ac3171997"}}';

        $data = json_decode($data, true);

        $fileName = "{$data['info']['junction_name']}_{$data['info']['quota_name']}_" . date('Ymd');

        $objPHPExcel = new PHPExcel();
        $objSheet = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('数据');

        $detailParams = [
            ['指标名', $data['info']['quota_name']],
            ['方向', $data['info']['direction']],
            ['基准时间', implode(' ~ ', $data['info']['base_time'])],
        ];
        foreach ($data['info']['evaluate_time'] as $key => $item) {
            $detailParams[] = ['评估时间'.$key, implode(' ~ ', $item)];
        }

        $detailParams[] = ['指标单位', $data['info']['quota_unit']];

        $objSheet->mergeCells('A1:F1');
        $objSheet->setCellValue('A1', $fileName);
        $objSheet->fromArray($detailParams, NULL, 'A4');

        $styles = $this->getExcelStyle();
        $objSheet->getStyle('A1')->applyFromArray($styles['title']);
        $rows_idx = count($detailParams) + 3;
        $objSheet->getStyle("A4:A{$rows_idx}")->getFont()->setSize(12)->setBold(true);

        $line = 6 + count($detailParams);

        if(!empty($data['base'])) {

            $table = $this->getExcelArray($data['base']);

            $objSheet->fromArray($table, NULL, 'A' . $line);

            $styles = $this->getExcelStyle();
            $rows_cnt = count($table);
            $cols_cnt = count($table[0]);
            $rows_index = $rows_cnt + $line - 1;
            $objSheet->getStyle("A{$line}:AW{$rows_index}")->applyFromArray($styles['content']);
            $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($styles['header']);
            $objSheet->getStyle("A{$line}:AW{$line}")->applyFromArray($styles['header']);

            $line += ($rows_cnt + 2);
        }

        if(!empty($data['evaluate'])) {

            foreach ($data['evaluate'] as $datum) {
                $table = $this->getExcelArray($datum);

                $objSheet->fromArray($table, NULL, 'A' . $line);

                $styles = $this->getExcelStyle();
                $rows_cnt = count($table);
                $cols_cnt = count($table[0]);
                $rows_index = $rows_cnt + $line - 1;
                $objSheet->getStyle("A{$line}:AW{$rows_index}")->applyFromArray($styles['content']);
                $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($styles['header']);
                $objSheet->getStyle("A{$line}:AW{$line}")->applyFromArray($styles['header']);

                $line += ($rows_cnt + 2);
            }
        }

        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

        header('Content-Type: application/x-xls;');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$fileName . 'xls');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: 0'); // Date in the past
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        ob_end_clean();
        $objWriter->save('php://output');
    }

    private function getExcelStyle() {
        $title_style = array(
            'font' => array(
                'bold' => true,
                'size '=> 16,
                'color'=>array(
                    'argb' => '00000000',
                ),
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array(
                    'argb' => '00FFFF00',
                ),
            ),
        );

        $headers_style = array(
            'font' => array(
                'bold' => true,
                'size '=> 12,
                'color'=>array(
                    'argb' => '00000000',
                ),
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array(
                    'argb' => '00DCDCDC',
                ),
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );

        $content_style = array(
            'borders' => array (
                'allborders' => array (
                    'style' => PHPExcel_Style_Border::BORDER_THIN,  //设置border样式
                    //'style' => PHPExcel_Style_Border::BORDER_THICK, //另一种样式
                    'color' => array ('argb' => '00000000'),     //设置border颜色
                ),
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );

        return array(
            'title'  => $title_style,
            'header' => $headers_style,
            'content'=> $content_style,
        );

    }

    private function getExcelArray($data)
    {
        $table = [];

        $first = array_column(current($data), 1);
        array_unshift($first, "日期-时间");
        $table[] = $first;
        foreach ($data as $key => $value) {
            $column = array_column($value, 0);
            array_unshift($column, $key);
            $table[] = $column;
        }

        return $table;
    }
}
