<?php

/**
 * 周报模块
 */
class Report_model extends CI_Model
{
    private $tb = 'report';

    /**
     * @var CI_DB_query_builder
     */
    private $db;

    /**
     * Report_model constructor.
     * @throws Exception
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
     * @param $cityId
     * @param $title
     * @param $type
     *
     * @return int
     */
    public function countReportByTitle($cityId, $title, $type)
    {
        return $this->db->where('city_id', $cityId)
            ->where('type', $type)
            ->where('title', $title)
            ->where('create_at >=', date("Y-m-d 00:00:00"))
            ->from($this->tb)
            ->count_all_results();
    }

    /**
     * 生成报告
     * @param $data['city_id'] int    城市ID
     * @param $data['title']   string 报告标题
     * @param $data['type']    int    报告类型 1，路口分析报告；2，路口优化对比报告；3，城市分析报告（周报）；4，城市分析报告（月报）10,路口报告,11,干线报告,12,区域报告
     * @param $data['file']    binary 二进制文件
     * @return mixed
     */
    public function insertReport($data)
    {
        $data['create_at'] = $data['create_at'] ?? date('Y-m-d H:i:s');
        $data['update_at'] = $data['update_at'] ?? date('Y-m-d H:i:s');

        $this->db->insert($this->tb, $data);

        return $this->db->insert_id();
    }

    /**
     * @param $cityId
     * @param $type
     * @param $pageNum
     * @param $pageSize
     * @param $namespace
     *
     * @return array
     */
    public function getCountJoinUploadFile($cityId, $type, $pageNum, $pageSize, $namespace)
    {
        $res = $this->db->select('count(*) as num')
            ->from('report')
            ->join('upload_files', 'upload_files.item_id = report.id')
            ->where('report.delete_at', "1970-01-01 00:00:00")
            ->where('upload_files.delete_at', "1970-01-01 00:00:00")
            ->where('upload_files.namespace', $namespace)
            ->where('report.city_id', $cityId)
            ->where('report.type', $type)
            // ->limit($pageSize, ($pageNum - 1) * $pageSize)
            ->get(); 
        return $res instanceof CI_DB_result ? $res->row_array() : $res;
    }

    /**
     * @param $cityId
     * @param $type
     * @param $pageNum
     * @param $pageSize
     * @param $namespace
     *
     * @return array
     */
    public function getSelectJoinUploadFile($cityId, $type, $pageNum, $pageSize, $namespace)
    {
        $res = $this->db->select('report.id, report.title, report.create_at, file_key, namespace,time_range')
            ->from('report')
            ->join('upload_files', 'upload_files.item_id = report.id')
            ->where('report.delete_at', "1970-01-01 00:00:00")
            ->where('upload_files.delete_at', "1970-01-01 00:00:00")
            ->where('upload_files.namespace', $namespace)
            ->where('report.city_id', $cityId)
            ->where('report.type', $type)
            ->order_by('report.id', 'DESC')
            ->limit($pageSize, ($pageNum - 1) * $pageSize)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }
}