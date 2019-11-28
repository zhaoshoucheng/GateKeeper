<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午7:19
 */

class Alarmworksheet_model extends CI_Model
{
    private $tb = 'alarm_worksheet';

    /**
     * @var \CI_DB_query_builder
     */
    protected $db;

    /**
     * Area_model constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->db = $this->load->database('default', true);
        $isExisted = $this->db->table_exists($this->tb);
        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
    }

    /**
     * 创建记录
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    public function insert($params)
    {
        return $this->db->insert('alarm_worksheet', $params);
    }

    public function find($params)
    {
        $res = $this->db->select("*")
            ->from("alarm_worksheet")
            ->where('id', $params["id"])
            ->get();
        return $res instanceof CI_DB_result ? $res->row_array() : $res;
    }

    /**
     * 获取数据列表
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    public function pageList($params)
    {
        $this->db->where("city_id",$params["city_id"]);
        if(!empty($params["to_group"])){
            $this->db->where("to_group",$params["to_group"]);
        }
        $this->db->from('alarm_worksheet');
        $total = $this->db->count_all_results();

        $offset = ($params["page_num"]-1) * $params["page_size"];
        $this->db->where("city_id",$params["city_id"]);
        if(!empty($params["to_group"])){
            $this->db->where("to_group",$params["to_group"]);
        }

        $result = $this->db->select('*')
            ->from('alarm_worksheet')
            ->limit($params["page_size"], $offset)
            ->forceMaster()
            ->order_by("id desc")
            ->get()
            ->result_array();
        return ["count"=>$total,"list"=>$result];
    }

    public function update($params)
    {
        return $this->db->where('id', $params["id"])
            ->update('alarm_worksheet', $params);
    }
}