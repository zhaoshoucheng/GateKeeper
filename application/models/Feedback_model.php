<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/21
 * Time: 上午10:08
 */

class Feedback_model extends CI_Model
{
    protected $tb = 'user_feedback';

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

        $isExisted = $this->db->table_exists($this->tb);

        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function insertFeedback($data)
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return $this->db->insert($this->tb, $data);
    }

}