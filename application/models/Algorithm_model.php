<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午7:19
 */

class Algorithm_model extends CI_Model
{
    private $tb = 'algorithm_test_selector';
    private $mtb = 'algorithm_test_model ';
    
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
        $isExisted = $this->db->table_exists($this->tb);
        if (!$isExisted) {
            throw new \Exception('数据表algorithm_test_selector不存在', ERR_DATABASE);
        }
        $isExisted = $this->db->table_exists($this->mtb);
        if (!$isExisted) {
            throw new \Exception('数据表algorithm_test_model不存在', ERR_DATABASE);
        }
    }

    public function getAllSelector(){
        $res = $this->db->select("*")
            ->from($this->tb)
            ->get();
        $selector = $res instanceof CI_DB_result ? $res->result_array() : $res;
        $res = $this->db->select("*")
            ->from($this->mtb)
            ->get();
        $model = $res instanceof CI_DB_result ? $res->result_array() : $res;
        return ["selector"=>$selector,"model"=>$model,];
    }
}