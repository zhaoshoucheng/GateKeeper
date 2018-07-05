<?php
/********************************************
# desc:    干线路口数据模型
# author:  niuyufu@didichuxing.com
# date:    2018-06-29
********************************************/

class Arterialjunction_model extends CI_Model
{
    private $tb = 'junction_index';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $is_existed = $this->db->table_exists($this->tb);
        if (!$is_existed) {
            return [];
        }

        $this->load->config('nconf');
        $this->load->model('waymap_model');
        $this->load->model('timing_model');
    }

    /**
     * 获取全城路口数据
     * @param data ['task_id']      interger 任务ID
     * @param data ['city_id']      interger 城市ID
     * @return array
     * 转换为json的格式:{"dataList":[{"logic_junction_id":"2017030116_4861479","lng":"117.16051","lat":"36.66729","name":"经十东路-凤山路"}],"junctionTotal":1}
     */
    public function getAllJunctions($data)
    {
        // 获取全城路口模板 没有模板就没有lng、lat = 画不了图
        $allCityJunctions = $this->waymap_model->getAllCityJunctions($data['city_id']);
        if (count($allCityJunctions) < 1 || !$allCityJunctions || !is_array($allCityJunctions)) {
            return [];
        }

        $resultData = [];
        $resultData['dataList'] = array_reduce($allCityJunctions, function ($v, $w) {
            if (empty($v)) {
                $v = [];
            }
            $v[] = array(
                "logic_junction_id" => $w["logic_junction_id"],
                "lng" => $w["lng"],
                "lat" => $w["lat"],
                "name" => $w["name"],
            );
            return $v;
        });
        $resultData['junctionTotal'] = count($resultData['dataList']);

        $junctionCenterFunc = function ($dataList) {
            $count_lng = 0;
            $count_lat = 0;
            $qcount = count($dataList);
            foreach ($dataList as $v) {
                $count_lng += $v['lng'];
                $count_lat += $v['lat'];
            }
            return ["lng" => round($count_lng / $qcount, 6), "lat" => round($count_lat / $qcount, 6),];
        };
        $resultData['center'] = $junctionCenterFunc($resultData['dataList']);
        return $resultData;
    }
}
