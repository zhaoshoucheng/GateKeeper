<?php
/********************************************
# desc:    评估数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-07-25
********************************************/

class Evaluate_model extends CI_Model
{
    private $tb = '';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
        $this->load->config('realtime_conf');
        $this->load->model('waymap_model');
    }

    public function getCityJunctionList($data)
    {
        $result = $this->waymap_model->getAllCityJunctions($data['city_id']);

        $result = array_map(function ($junction) {
            return [
                'logic_junction_id' => $junction['logic_junction_id'],
                'junction_name' => $junction['name'],
                'lng' => $junction['lng'],
                'lat' => $junction['lat']
            ];
        }, $result);


        $lngs = array_column($result, 'lng');
        $lats = array_column($result, 'lat');

        $center['lng'] = count($lngs) == 0 ? 0 : (array_sum($lngs) / count($lngs));
        $center['lat'] = count($lats) == 0 ? 0 : (array_sum($lats) / count($lats));

        return [
            'dataList' => $result,
            'center' => $center
        ];
    }

    public function getQuotaList($data)
    {
        $realTimeQuota = $this->config->item('real_time_quota');

        $realTimeQuota = array_map(function ($key, $value) {
            return [
                'name' => $value['name'],
                'key' => $key,
                'unit' => $value['unit']
            ];
        }, $realTimeQuota);

        return ['dataList' => array_values($realTimeQuota)];
    }

    public function getDirectionList($data)
    {
        $result = $this->waymap->getFlowsInfo($data['junction_id']);

        $result = $result['junction_id'] ?? [];

        $result = array_map(function ($key, $value) {
            return [ $key, $value ];
        }, $result);

        return [ 'dataList' => $result ];
    }
}