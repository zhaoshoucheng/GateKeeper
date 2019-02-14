<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/21
 * Time: 上午10:08
 */

class Feedback_model extends CI_Model
{
    /**
     * @var CI_DB_query_builder
     */
    protected $db;

    /**
     * Feedback_model constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);
    }

    public function insert($table, $data)
    {
        $isExisted = $this->db->table_exists($table);
        if (!$isExisted) {
            throw new \Exception($table." not exists! ", ERR_DATABASE);
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->insert($table, $data);
    }
}
