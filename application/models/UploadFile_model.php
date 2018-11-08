<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/29
 * Time: 下午3:59
 */

class UploadFile_model extends CI_Model
{
    private $tb = 'upload_files';

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

        $is_existed = $this->db->table_exists($this->tb);

        if (!$is_existed) {
            throw new Exception('数据表 ' . $this->tb . ' 不存在', ERR_DATABASE);
        }
    }

    public function insertUploadFile($data)
    {
        $data['create_at'] = $data['create_at'] ?? date('Y-m-d H:i:s');
        $data['update_at'] = $data['update_at'] ?? date('Y-m-d H:i:s');

        $this->db->insert($this->tb, $data);

        return $this->db->insert_id();
    }
}