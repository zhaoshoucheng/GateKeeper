<?php
/**
* 时段优化数据模型
*/

date_default_timezone_set('Asia/Shanghai');
class Timeframeoptimize_model extends CI_Model
{
    private $tb = 'junction_index';
    private $db = '';

    function __construct() {
        parent::__construct();
        $this->db = $this->load->database('default', true);
    }

    /**
     * 优化-单点时段优化路口列表
     * @param data['task_id']      interger 任务ID
     * @param data['city_id']      interger 城市ID
     * @return array 转换为json的格式:{"dataList":[{"logic_junction_id":"2017030116_4861479","lng":"117.16051","lat":"36.66729","name":"经十东路-凤山路"}],"junctionTotal":1}
     */
    public function getAllJunctions($data)
    {
        // 获取全城路口模板 没有模板就没有lng、lat = 画不了图
        $allCityJunctions = $this->waymap_model->getAllCityJunctions($data['city_id']);
        if (count($allCityJunctions) < 1 || !$allCityJunctions || !is_array($allCityJunctions)) {
            return [];
        }
        // 获取任务路口ids
        $where = 'task_id = ' . $data['task_id'] . ' and type = 0';
        $query = $this->db->select('*')
            ->from($this->tb)
            ->where($where)
            ->group_by('junction_id')
            ->get();
        $newDataJids = [];
        foreach ($query->result_array() as $v) {
            $newDataJids[] = $v['junction_id'];
        }
        // 验证路口是否存在
        $result_data['dataList'] = array_reduce($allCityJunctions,function($v,$w) use ($newDataJids){
            if(empty($v)) {
                $v = [];
            }
            if(isset($w["logic_junction_id"]) && in_array($w["logic_junction_id"],$newDataJids)){
                $v[] = array(
                    "logic_junction_id"=>$w["logic_junction_id"],
                    "lng"=>$w["lng"],
                    "lat"=>$w["lat"],
                    "name"=>$w["name"],
                );
            }
            return $v;
        });
        $result_data['junctionTotal'] = count($result_data['dataList']);
        return $result_data;
    }
}