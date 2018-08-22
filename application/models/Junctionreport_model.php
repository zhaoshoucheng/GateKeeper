<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/21
 * Time: 上午10:06
 */


class Junctionreport_model extends CI_Model
{

    protected $tb = 'flow_duration_v6_';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
    }


}