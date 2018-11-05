<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/11/5
 * Time: 上午11:40
 */

namespace Services;

/**
 * Class PeriodReportService
 * @package Services
 * @property \Period_model $period_model
 */
class PeriodReportService extends BaseService
{
    const WEEK  = 3;
    const MONTH = 4;

    const ALLDAY  = 1;
    const MORNING = 2;
    const NIGHT   = 3;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('period_model');
        $this->load->library('EvaluateQuota');
        $this->load->model('waymap_model');
    }

    /**
     * @param $params
     *
     * @throws \Exception
     */
    public function overview($params)
    {
        $cityId   = $params['city_id'];
        $cityName = $params['city_name'];
        $type     = $params['type'];

        if ($type == self::WEEK) {//周报
            $lastTime    = $this->getLastWeek();
            $prelastTime = $this->getLastWeek(2);
            $lastData    = $this->period_model->getCityWeekData($cityId, $lastTime['start_time']);
            $prelastData = $this->period_model->getCityWeekData($cityId, $prelastTime['start_time']);
        } else {
            $lastTime    = $this->getLastMonth();
            $prelastTime = $this->getLastMonth(2);
            $lastData    = $this->period_model->getCityMonthData($cityId, explode('-', $lastTime['start_time'])[0], explode('-', $lastTime['start_time'])[1]);
            $prelastData = $this->period_model->getCityMonthData($cityId, explode('-', $prelastTime['start_time'])[0], explode('-', $prelastTime['start_time'])[1]);
        }

        if (empty($lastData)) {
            $lastData = [
                'stop_delay' => 0,
                'spillover_freq' => 0,
                'oversaturation_freq' => 0,
            ];
        } else {
            $lastData = $lastData[0];
        }
        if (empty($prelastData)) {
            $prelastData = [
                'stop_delay' => 0,
                'spillover_freq' => 0,
                'oversaturation_freq' => 0,
            ];
        } else {
            $prelastData = $prelastData[0];
        }

        $stop_delay_MoM          = ($lastData['stop_delay'] - $prelastData['stop_delay']) / ($prelastData['stop_delay'] == 0 ? 1 : $prelastData['stop_delay']) * 100;
        $spillover_freq_MoM      = ($lastData['spillover_freq'] - $prelastData['spillover_freq']) / ($prelastData['spillover_freq'] == 0 ? 1 : $prelastData['spillover_freq']) * 100;
        $oversaturation_freq_MoM = ($lastData['oversaturation_freq'] - $prelastData['oversaturation_freq']) / ($prelastData['oversaturation_freq'] == 0 ? 1 : $prelastData['oversaturation_freq']) * 100;
        if ($type == self::WEEK) {
            $overviewStr = "本周(" . self::formatTime($lastTime['start_time']) . "-" . self::formatTime($lastTime['end_time']) . ")" . $cityName . "区拥堵程度相对严重,";
            $change      = $stop_delay_MoM > 0 ? "增长" : "减少";
            $overviewStr .= "市区整体平均延误" . round($lastData['stop_delay'], 2) . "秒,环比上周" . $change . abs(round($stop_delay_MoM, 2)) . "%。";
            $change      = $spillover_freq_MoM > 0 ? "增长" : "减少";
            $overviewStr .= "发生溢流路口共" . $lastData['spillover_freq'] . "路口次,环比上周问题路口" . $change . abs(round($spillover_freq_MoM, 2)) . "%。";
            $change      = $oversaturation_freq_MoM > 0 ? "增长" : "减少";
            $overviewStr .= "过饱和路口" . $lastData['oversaturation_freq'] . "路口次,环比上周问题" . $change . abs(round($oversaturation_freq_MoM, 2)) . "%。";

        } else {
            $overviewStr = "本月(" . self::formatTime($lastTime['start_time']) . "-" . self::formatTime($lastTime['end_time']) . ")" . $cityName . "区拥堵程度相对严重,";
            $change      = $stop_delay_MoM > 0 ? "增长" : "减少";
            $overviewStr .= "市区整体平均延误" . round($lastData['stop_delay'], 2) . "秒,环比上月" . $change . abs(round($stop_delay_MoM, 2)) . "%。";
            $change      = $spillover_freq_MoM > 0 ? "增长" : "减少";
            $overviewStr .= "发生溢流路口共" . $lastData['spillover_freq'] . "路口次,环比上月问题路口" . $change . abs(round($spillover_freq_MoM, 2)) . "%。";
            $change      = $oversaturation_freq_MoM > 0 ? "增长" : "减少";
            $overviewStr .= "过饱和路口" . $lastData['oversaturation_freq'] . "路口次,环比上月问题" . $change . abs(round($oversaturation_freq_MoM, 2)) . "%。";
        }

        return [
            'summary' => $overviewStr,
            'start_time' => self::formatTime($lastTime['start_time']),
            'end_time' => self::formatTime($lastTime['end_time']),
        ];
    }

    /**
     * @param int $t
     *
     * @return array
     */
    private function getLastWeek($t = 1)
    {

        if (intval(date('w')) == 1) {
            $mon = $t * -1;
        } else {
            $mon = ($t + 1) * -1;
        }

        return [
            'start_time' => date('Y-m-d', strtotime($mon . ' monday', time())),
            'end_time' => date('Y-m-d', strtotime((($t) * -1) . ' sunday', time())),
        ];
    }

    /**
     * @param int $t
     *
     * @return array
     */
    private function getLastMonth($t = 1)
    {
        return [
            'start_time' => date('Y-m-01', strtotime((($t) * -1) . ' month')),
            'end_time' => date('Y-m-t', strtotime((($t) * -1) . ' month')),
        ];
    }

    /**
     * @param $time
     *
     * @return mixed
     */
    private function formatTime($time)
    {
        return str_replace('-', '.', $time);
    }

    public function stopDelayTable($params)
    {
        $cityId = $params['city_id'];
        $type   = $params['type'];

        if ($type == self::WEEK) {
            $lastTime    = $this->getLastWeek();
            $prelastTime = $this->getLastWeek(2);
        } else {
            $lastTime    = $this->getLastMonth();
            $prelastTime = $this->getLastMonth(2);

        }
        $dateList    = self::getDateFromRange($lastTime['start_time'], $lastTime['end_time']);
        $predateList = self::getDateFromRange($prelastTime['start_time'], $prelastTime['end_time']);

        $hourDate = $this->period_model->getCityHourData($cityId, $dateList);
        if (empty($hourDate)) {
            return [];
        }

        $final_data = [];
        $evaluate   = new \EvaluateQuota();

        if ($type == self::WEEK) {//周报8条线
            $final_data = $evaluate->getCityStopDelayAve($hourDate);

        } else {//月报两条线
            $prehourDate = $this->period_model->getCityHourData($cityId, $predateList);

            $charData                  = $evaluate->getCityStopDelayAve($hourDate);
            $precharData               = $evaluate->getCityStopDelayAve($prehourDate);
            $final_data['period']      = $charData['total'];
            $final_data['last_period'] = $precharData['total'];
        }

        return [
            'info' => [
                'start_time' => $lastTime['start_time'],
                'end_time' => $lastTime['end_time'],
            ],
            'base' => $final_data,
        ];
    }

    /**
     * @param $startdate
     * @param $enddate
     *
     * @return array
     */
    private function getDateFromRange($startdate, $enddate)
    {

        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);

        // 计算日期段内有多少天
        $days = ($etimestamp - $stimestamp) / 86400 + 1;

        // 保存每天日期
        $date = [];

        for ($i = 0; $i < $days; $i++) {
            $date[] = date('Y-m-d', $stimestamp + (86400 * $i));
        }

        return $date;
    }
}