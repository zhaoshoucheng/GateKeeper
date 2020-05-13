<?php
class FusionMappingInfo_model extends CI_Model
{
    private $tb = 'fusion_mapping_info';

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
    public function getFusionJuncList($cityID)
    {
        $res = $this->db->select("DISTINCT `junc_id`")
            ->from($this->tb)
            ->where('city_id', $cityID)
            ->where_in('service_name', ["suzhou_gusu_bayonet","suzhou_gusu_loop"])
            ->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }
}