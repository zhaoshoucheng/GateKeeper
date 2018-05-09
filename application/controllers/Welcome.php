<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Didi\Cloud\ItsMap\City as CityService;

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
        /*
        $aRet = $this->task_model->getTask("demo", 1, 1, 1);
        */

        // http
        /*
        $ret = httpPOST('http://100.90.164.31:8088/signalpro/api/task/getList', ['a' => 1]);
        $ret = httpGET('http://100.90.164.31:8088/signalpro/api/task/getList', ['b' => 2]);
        */

        // eloquent orm
        /*
        $cityService = new CityService();
        $response = $cityService->all();
        \Didi\Cloud\ItsMap\MapManager::queryLog();
        */

        // thrift
        $flowService = new Didi\Cloud\ItsMap\Flow();
        $response = $flowService->allByJunctionWithLinkAttr('2017030116_103472', '2017030116');

        echo json_encode("ok");
    }
}
