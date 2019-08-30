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
    private $logTb = 'adapt_timing_log';

    /**
     * @var \CI_DB_query_builder
     */
    protected $db;
    protected $logDb;

    /**
     * Area_model constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);
        $this->logDb = $this->load->database('its_trace_log', true);

        $isExisted = $this->db->table_exists($this->tb);
        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
        $isExisted = $this->logDb->table_exists($this->logTb);
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
    /**
     * 删除优化日志
     * @param $deleteDay Y 删除几天前
     * @return bool
     */
    public function deleteAdaptLog($deleteDay="-3 day")
    {
        //筛选第100000条数据
        $result = $this->logDb->select('*')
            ->from('adapt_timing_log')
            ->limit(1,99999)
            ->order_by("id asc")
            ->get()
            ->result_array();
        if(strtotime(end($result)["log_time"])<strtotime($deleteDay)){
            $this->logDb->where('id<', end($result)["id"])->delete('adapt_timing_log');
        }
        return true;
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
        if(!isset($data["trace_id"])){
            $data["trace_id"] = "";
        }
        $data = array_merge($params,$data);
        return $this->logDb->insert('adapt_timing_log', $data);
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
            $this->logDb->where("trace_id",$params["trace_id"]);
        }
        if(!empty($params["dltag"])){
            $this->logDb->where("dltag",$params["dltag"]);
        }
        if(!empty($params["rel_id"])){
            $this->logDb->where("rel_id",$params["rel_id"]);
        }
        $this->logDb->where("type",$params["type"]);
        $this->logDb->from('adapt_timing_log');
        $total = $this->logDb->count_all_results();

        $offset = $params["per_page"] ?? 0;
        $this->logDb->where("type",$params["type"]);
        if(!empty($params["trace_id"])){
            $this->logDb->where("trace_id",$params["trace_id"]);
        }
        if(!empty($params["dltag"])){
            $this->logDb->where("dltag",$params["dltag"]);
        }
        if(!empty($params["rel_id"])){
            $this->logDb->where("rel_id",$params["rel_id"]);
        }
        $this->logDb->where("type",$params["type"]);
        $result = $this->logDb->select('*')
            ->from('adapt_timing_log')
            ->limit($params["page_size"], $offset)
            ->forceMaster()
            ->order_by("log_time desc,id desc")
            ->get()
            ->result_array();
        return [$total,$result];
    }
}