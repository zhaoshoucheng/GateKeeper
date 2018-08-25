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

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
    }

    public function addFeedback($data)
    {
        $this->db->insert($this->tb, $data);

        return 'success';
    }

}