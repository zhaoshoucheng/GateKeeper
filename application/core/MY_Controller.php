<?php
/**********************************************
* 基础类
* user:ningxiangbing@didichuxing.com
* date:2018-03-01
**********************************************/

class MY_Controller extends CI_Controller {

	public $errno = 0;
	public $errmsg = '';
	public $output_data=array();
	public $templates = array();
	protected $debug = false;

	public function __construct(){
		parent::__construct();
		date_default_timezone_set('Asia/Shanghai');
		if($this->input->post('debug')){
			$this->debug = true;
		}
	}

	public function response($data, $errno = 0, $errmsg = '') {
		$this->output_data = $data;
		$this->errno = $errno;
		$this->errmsg = $errmsg;
		$this->output->set_content_type('application/json');
	}

	public function _output(){
		if($this->errno >0 && empty($this->errmsg)){
			$errmsgMap = $this->config->item('errmsg');
			$this->errmsg = $errmsgMap[$this->errno];
		}
		if(!empty($this->templates)){
			foreach ($this->templates as $t){
				echo $this->load->view($t, array(), true);
			}
		} else {
			$output = array(
				'errno' => $this->errno,
				'errmsg' => $this->errmsg,
				'data' => $this->output_data
			);
			echo json_encode($output);
		}
	}
}