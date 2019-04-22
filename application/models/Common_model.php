<?php
/********************************************
# desc:    公共方法模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-08-23
********************************************/

/**
 * Class Common_model
 * @property Redis_model $redis_model
 */
class Common_model extends CI_Model
{
    private $db = '';
    private $dmp_db = '';

    public function __construct()
    {
        parent::__construct();
        $this->db = $this->load->database('default', true);

    }

    /**
     * 获取路口对应信号机相位信息
     * @param $junctionId
     */
    public function getTimingMovementNames($junctionId) {
        $res = httpGET("http://10.88.128.40:8000/ipd-cloud/signal-platform/profile/base/current?junctionId=".$junctionId, []);
        $ret = json_decode($res,true);
        $flowInfos = $ret[0]["tod_schedule"][0]["tod"][0]["phase_time"][0]["flows"] ?? [];

        $result = [];
        foreach ($flowInfos as $flowInfo){
            preg_match("/@(.*)@(.*)/ims",$flowInfo,$matches);
            if(count($matches)==3 && !empty($matches[2])){
                $result[$matches[1]] = $matches[2];
            }
        }
        return $result;
    }

    /**
     * 查询
     * @param $table    table name
     * @param $select   select colum
     * @param $where    where
     * @param $groupby  group by
     * @param $page     offset
     * @param $pagesize count
     * @return mixed
     */
    public function search($table, $select = '*', $where = [], $groupby = '', $page = 0, $pagesize = 0)
    {
        $isExisted = $this->db->table_exists($table);
        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }

        $this->db->select($select);
        $this->db->from($table);
        if (!empty($where)) {
            $this->db->where($where);
        }

        if (!empty($groupby)) {
            $this->db->group_by($groupby);
        }

        if ($pagesize >= 1) {
            $this->db->limit($pagesize, $page);
        }

        return $this->db->get()->result_array();
    }

    /**
     * dmp查询
     * @param $table    table name
     * @param $select   select colum
     * @param $where    where
     * @param $groupby  group by
     * @param $page     offset
     * @param $pagesize count
     * @return mixed
     */
    public function dmpSearch($table, $select = '*', $where = [], $groupby = '', $page = 0, $pagesize = 0)
    {
        $this->dmp_db = $this->load->database('dmp_captain', true);

        $isExisted = $this->dmp_db->table_exists($table);
        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }

        $this->dmp_db->select($select);
        $this->dmp_db->from($table);
        if (!empty($where)) {
            $this->dmp_db->where($where);
        }

        if (!empty($groupby)) {
            $this->dmp_db->group_by($groupby);
        }

        if ($pagesize >= 1) {
            $this->dmp_db->limit($pagesize, $page);
        }

        return $this->dmp_db->get()->result_array();
    }

    /**
     * 获取v5开城列表
     * @param $cityId long 城市ID
     * @return mixed
     */
    public function getV5DMPCityID()
    {
        //return $this->getAdaptCityIDS();
        $table = 'dmp_city_config';
        $select = 'city_id, city_name';
        $where = [
            'sys_id'   => "signal_control_pro",
            'extra' => "v5",
            'status' => "1",
        ];

        $res = $this->dmpSearch($table, $select, $where);
        if (!$res) {
            return [];
        }

        $result = [];
        foreach ($res as $k=>$v) {
            $result[] = (int)$v['city_id'];
        }
        return $result;
    }


    /**
     * 获取实时配时开城列表
     * @param $cityId long 城市ID
     * @return mixed
     */
    public function getAdaptCityIDS()
    {
        $table = 'open_city';
        $select = 'city_id, city_name';
        $where = [
            'is_deleted'   => "0",
            'open_real_type1' => "1",
        ];

        $res = $this->search($table, $select, $where);
        if (!$res) {
            return [];
        }

        $result = [];
        foreach ($res as $k=>$v) {
            $result[] = (int)$v['city_id'];
        }
        return $result;
    }
}
