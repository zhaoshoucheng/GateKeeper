<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->helper('http');
        $this->load->model('task_model');
    }


    /**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$this->load->view('welcome_message');
	}


    public function straceDemo()
    {
        // mysql
        $aRet = $this->task_model->getTask("demo", 1, 1, 1);

        $ret = httpPOST('http://www.didichuxing.com', ['a' => 1]);
        $ret = httpGET('http://www.didichuxing.com', ['b' => 2]);

        $this->output_data = [
            'cycle_task' => $cycle_task,
            'custom_task' => $custom_task,
        ];
    }
}
