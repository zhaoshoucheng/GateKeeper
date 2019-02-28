<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午7:19
 */

class Adapt_model extends CI_Model
{
    private $tb = 'adapt_timing_mirror';

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
     * 获取指定路口的自适应配时信息
     *
     * @param string $logicJunctionId
     * @param string $select
     *
     * @return array
     */
    public function getAdaptByJunctionId($logicJunctionId, $select = '*')
    {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('logic_junction_id', $logicJunctionId)
            ->get();

        return $res instanceof CI_DB_result ? $res->row_array() : $res;
    }

    /**
     * 更新自适应配时信息表
     *
     * @param $logicJunctionId
     * @param $data
     *
     * @return bool
     */
    public function updateAdapt($logicJunctionId, $data)
    {
        return $this->db->where('logic_junction_id', $logicJunctionId)
            ->update('adapt_timing_mirror', $data);
    }

    /**
     * 删除优化日志
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    public function deleteAdaptLog($params)
    {
        $data = [
            'created_at' => date("Y-m-d H:i:s"),
        ];
        $data = array_merge($params,$data);
        return $this->db->insert('adapt_timing_log', $data);
    }

    /**
     * 写入优化日志
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    public function insertAdaptLog($params)
    {
        $data = [
            'created_at' => date("Y-m-d H:i:s"),
        ];
        $data = array_merge($params,$data);
        return $this->db->insert('adapt_timing_log', $data);
    }


    /**
     * 获取调度数据列表
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    public function pageList($params)
    {
        if(!empty($params["trace_id"])){
            $this->db->where("trace_id",$params["trace_id"]);
        }
        if(!empty($params["dltag"])){
            $this->db->where("dltag",$params["dltag"]);
        }
        if(!empty($params["rel_id"])){
            $this->db->where("rel_id",$params["rel_id"]);
        }
        $this->db->where("type",$params["type"]);
        $this->db->from('adapt_timing_log');
        $total = $this->db->count_all_results();

        $offset = $params["per_page"] ?? 0;
        $this->db->where("type",$params["type"]);
        if(!empty($params["trace_id"])){
            $this->db->where("trace_id",$params["trace_id"]);
        }
        if(!empty($params["dltag"])){
            $this->db->where("dltag",$params["dltag"]);
        }
        if(!empty($params["rel_id"])){
            $this->db->where("rel_id",$params["rel_id"]);
        }
        $this->db->where("type",$params["type"]);
        $result = $this->db->select('*')
            ->from('adapt_timing_log')
            ->limit($params["page_size"], $offset)
            ->order_by("log_time desc,id desc")
            ->get()
            ->result_array();;
        return [$total,$result];
    }
}