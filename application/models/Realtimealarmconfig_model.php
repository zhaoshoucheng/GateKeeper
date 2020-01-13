<?php
/********************************************
# desc:    优化参数管理数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-11-19
********************************************/

class Realtimealarmconfig_model extends CI_Model
{
    private $tb = 'optimized_realtime_alarm_config';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

    }

    public function getParameterLimit($cityID,$areaID="",$isDefault="") 
    {
        $builder = $this->db->select('*')
                        ->from($this->tb)
                        ->where('city_id', $cityID);
        if(!empty($areaID)){
            $builder->where('area_id', $areaID);
        }else{
            $builder->where('area_id', "-1");
        }
        $res = $builder->order_by("hour asc")->get()->result_array();
        return $res;
    }
    
    /**
     * 更新优化配置的参数
     *
     * @param $data
     * @return bool 更新的结果
     */
    public function updateParameter($cityID, $areaID, $data)
    {
        unset($data['create_at']);
        unset($data['update_at']);
        unset($data['id']);
        $hour = $data['hour'];
        $data['city_id']=$cityID;
        $data['area_id']=$areaID;
        $data['update_at']=date("Y-m-d H:i:s");
        $res = $this->db->select('*')
                    ->from($this->tb)
                    ->where('city_id', $cityID)
                    ->where('area_id', $areaID)
                    ->where('hour', $hour)
                    ->get()->result_array();
        $changed = false;
        if(empty($res)){
            $changed = true;
        }
        if (empty($res)) {
            return [$this->db->insert($this->tb, $data),$changed];
        }

        if(!compareArrayKeysEqual($res[0],$data,["oversatutrailnumpara","greenslacktrailnumpara","stopdelaypara","multistopupperbound","multistoplowerbound","nonestopupperbound","nonestoplowerbound","queuelengthupperbound","queuelengthlowerbound","queueratiolowbound","spillovertrailnumpara","queueratiopara","spilloveralarmtrailnumpara","spilloverstopdelaypara"])){
            $changed = true;
        }
        return [$this->db->where('id', $res[0]['id'])->update($this->tb, $data),$changed];
    }
}
