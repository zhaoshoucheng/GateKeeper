<?php
/**
* 时段优化数据模型
*/

date_default_timezone_set('Asia/Shanghai');
class Timeframeoptimize_model extends CI_Model
{
    private $tb = 'junction_index';
    private $db = '';

    function __construct() {
        parent::__construct();
        $this->its_tool = $this->load->database('default', true);
    }
}