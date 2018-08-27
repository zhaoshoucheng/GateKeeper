<?php
/********************************************
# desc:    路口数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-03-05
********************************************/

class Gift_model extends CI_Model
{
    private $db = '';
    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $is_existed = $this->db->table_exists($this->tb);
        if (!$is_existed) {
            // 添加日志
            return [];
        }
        $this->load->config('nconf');
    }

    public function Upload(){
        
    }
}
