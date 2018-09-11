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
	    print_r($_SERVER);
		$this->load->view('welcome_message');
	}

	public function test(){
        com_log_warning('_asynctask_index_error', 0, '1123123', []);
//        echo "123213";
//        exit;
//        $tab = $_POST;
//        $file = $_FILES;
//        $_POST = xss_clean($_POST);
//        $_POST = xss_clean($_POST);
//        print_r($file);exit;
//        ob_start();
//        echo "ob_started";
//        $obLevel = ob_get_level();
//        ob_end_flush();
//        print_r($obLevel);
//        throw new \Exception("hello_exception");
//        $a[11];
        $result = httpGET("http://10.95.100.106:8890/test/hello");
        var_dump($result);exit;
        echo "13123";
        $qArr = "1122";
        $res = ["awae","dadad"];
        com_log_warning('_itstool_waymap_getConnectionAdjJunctions_error', 0, "adasdsad", compact("qArr","res"));
        exit;
    }

    public function operateLog()
    {
        //追加相应信息
        operateLog("niuyufu", "adapt_area_switch_edit", 516, ["old" => "status=0", "new" => "status=1",]);
        echo "operateLog";
    }

    public function getAreaJunctionList(){
        $jstr = '{
	"errorCode": 0,
	"errorMsg": "",
	"data": {
		"junctionList": [{
			"lat": 36.66607,
			"lng": 116.99921,
			"logic_junction_id": "2017030116_4875782",
			"name": "纬一路_经二路_4875782",
			"type": 1,
			"status": 1
		}, {
			"lat": 36.66607,
			"lng": 116.99921,
			"logic_junction_id": "2017030116_4875783",
			"name": "纬一路_经二路_4875782",
			"type": 1,
			"status": 1
		}, {
			"lat": 36.66607,
			"lng": 116.99921,
			"logic_junction_id": "2017030116_4875784",
			"name": "纬一路_经二路_4875782",
			"type": 1,
			"status": 1
		}]
	}
}';
        echo $jstr;exit;
    }

    public function getAreaList(){
        $jstr = '{
	"errorCode": 0,
	"errorMsg": "",
	"data": {
		"areaList": [{
			"area_id": 1,
			"area_name": "测试区域",
			"center_lat": 36.6692375,
			"center_lng": 117.0065725,
			"status": 1,
			"llat": 36.703935,
			"llng": 116.936191,
			"rlat": 36.63454,
			"rlng": 117.076954,
			"junction_num": 100,
			"adaptive_num": 10
		}, {
			"area_id": 2,
			"area_name": "测试区域",
			"center_lat": 36.6692375,
			"center_lng": 117.0065725,
			"status": 1,
			"llat": 36.703935,
			"llng": 116.936191,
			"rlat": 36.63454,
			"rlng": 117.076954,
			"junction_num": 100,
			"adaptive_num": 10
		}, {
			"area_id": 3,
			"area_name": "测试区域",
			"center_lat": 36.6692375,
			"center_lng": 117.0065725,
			"status": 1,
			"llat": 36.703935,
			"llng": 116.936191,
			"rlat": 36.63454,
			"rlng": 117.076954,
			"junction_num": 100,
			"adaptive_num": 10
		}]
	}
}';
        echo $jstr;exit;
    }


    public function getOneAreaList(){
        $jstr = '{
	"errorCode": 0,
	"errorMsg": "",
	"data": {
		"areaList": [{
			"area_id": 1,
			"area_name": "测试区域",
			"center_lat": 36.6692375,
			"center_lng": 117.0065725,
			"status": 1,
			"llat": 36.703935,
			"llng": 116.936191,
			"rlat": 36.63454,
			"rlng": 117.076954,
			"junction_num": 100,
			"adaptive_num": 10
		}]
	}
}';
        echo $jstr;exit;
    }

    public function getCurrentAdaptTimingInfo(){
        $jstr = '{
	"errorCode": 0,
	"errorMsg": "",
	"data": {
		"flag": 1,
		"dispatch": {
			"logic_junction_id": "2017030116_73316908",
			"source": 3,
			"comment": "调度1",
			"weekend": [1, 2, 3, 4, 5, 6, 7],
			"start_date": "2018-01-01",
			"end_date": "2018-03-03"
		},
		"tod": [{
			"tod_id": 123,
			"plan_id": 123,
			"plan_num": 1,
			"stage": [{
				"num": 1,
				"start_time": 0,
				"duration": 20,
				"yellow_length": 3,
				"green_min": 7,
				"green_length": 57,
				"green_max": 60,
				"allred_length": 0,
				"ring_id": 1,
				"movements": [{
					"flow": {
						"type": 0,
						"logic_flow_id": "2017030116_i_576765780_2017030116_o_576765800",
						"comment": "东直"
					},
					"channel": 1
				}]
			}, {
				"num": 2,
				"start_time": 20,
				"duration": 30,
				"yellow_length": 3,
				"green_min": 7,
				"green_length": 57,
				"green_max": 60,
				"allred_length": 0,
				"ring_id": 1
			}],
			"extra_time": {
				"offset": 54,
				"cycle": 100,
				"tod_start_time": "00:00:00",
				"tod_end_time": "12:00:00"
			},
			"movement_timing": [{
				"movement_id": 123,
				"channel": 1,
				"phase_id": 1,
				"phase_seq": 10,
				"timing": [{
					"state": 1,
					"start_time": 0,
					"duration": 10,
					"max": 15,
					"min": 9
				}, {
					"state": 2,
					"start_time": 10,
					"duration": 3,
					"max": null,
					"min": null
				}],
				"flow": {
					"type": 0,
					"logic_flow_id": "2017030116_i_576765780_2017030116_o_576765800",
					"comment": "东直"
				}
			}]
		}]
	}
}';
        echo $jstr;exit;
    }


    public function getAdaptiveJunctionList(){
        $jstr = '{
	"errorCode": 0,
	"errorMsg": "",
	"data": [{
		"logic_junction_id": "2017030116_10939810",
		"is_opt": 1,
		"is_upload": 1,
		"is_upsigntime": 1,
		"source": 3
	}, {
		"logic_junction_id": "2017030116_10939811",
		"is_opt": 1,
		"is_upload": 0,
		"is_upsigntime": 1,
		"source": 3
	}]
}';
        echo $jstr;exit;
    }

    //获取最新信号机时间
    public function getSignUploadTime(){
        $jstr = '{
	"errorCode": 0,
	"errorMsg": "",
	"data": "2018-08-02 16:32:03"
}';
        echo $jstr;exit;
    }

    public function demo()
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
        /*
        $flowService = new Didi\Cloud\ItsMap\Flow();
        $response = $flowService->allByJunctionWithLinkAttr('2017030116_103472', '2017030116');
        */

        // redis
        /*
        $this->load->helper('redis');
        $redis = RedisMgr::getInstance('default');
        $val = $redis->get("test.key");
        */

        // common
        com_log_notice('_itstool_welcome_demo_restart', ['a' => 1, 'b' => 2]);
        com_log_warning('_itstool_welcome_demo_id_error', ['id' => 1]);

        echo json_encode("ok");
    }

    public function token() {
    	$secret = '310173de4b64b866e6f0cf4841178b84';
    	$get = $this->input->get();
    	$post = $this->input->post();
    	$params = array_merge($get, $post);
    	var_dump(gen1($params, $secret));
    	var_dump(gen2($params, $secret));
    	var_dump(gen3($params, $secret));
    }

    private function gen1($params, $secret) {
    	ksort($params);
    	$str = '';
    	foreach ($params as $k => $v) {
    	    $str .= "$k=" . urldecode($v);
    	}
    	$str .= $secret;
    	return md5($str);
    }

    private function gen2($params, $secret) {
    	ksort($params);
    	$str = http_build_query($params) . '&' . $secret;
    	return md5($str);
    }

    private function gen3($params, $secret) {
    	ksort($params);
    	$query_str = http_build_query($params);
    	$str = substr(md5($query_str . "&" . $secret), 7, 16);
    	return $str;
    }
}
