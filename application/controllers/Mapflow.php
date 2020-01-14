<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Didi\Cloud\ItsMap\City;
use Didi\Cloud\ItsMap\Collection as Collection;

use Didi\Cloud\ItsMap\Node;
use Didi\Cloud\ItsMap\Junction;
use Didi\Cloud\ItsMap\MapVersion;
use Didi\Cloud\ItsMap\Services\RoadNet;
use Didi\Cloud\ItsMap\Flow as FlowService;
use Services\MapFlowService;

class Mapflow extends MY_Controller
{
    private $mapFlowService;
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('http');
        $this->load->config('nconf');
        $this->load->model('waymap_model');
        $this->mapFlowService = new MapFlowService();
    }

    public function simplifyFlows()
    {
        $logic_junction_id = $this->input->get_post('logic_junction_id');
        $version = $this->input->get_post('version');
        $logic_flow_ids = $this->input->get_post('logic_flow_ids');
        $with_hidden = $this->input->get_post('with_hidden');
        try {
            $data = [
                        'logic_junction_id' => $logic_junction_id,
                        'version' => $version,
                        'logic_flow_ids' => $logic_flow_ids,
                        'token'     => $this->config->item('waymap_token'),
                        'user_id'   => $this->config->item('waymap_userid'),
                        'with_hidden'   => $with_hidden,   //是否包含隐藏相位
                    ];
            $ret = httpPOST($this->config->item('waymap_interface') . '/signal-map/MapFlow/simplifyFlows', $data);
            $ret = json_decode($ret, true);
            if ($ret['errorCode'] == -1) {
                $this->errno = -1;
                $this->errmsg = 'simplifyFlows error';
                return;
            }
            $this->output_data = $ret['data'];
        } catch (Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
    }

    public function getFlows(){
        $params = $this->input->get(null, true);
        if (empty($params['logic_junction_id'])) {
            throw new \Exception('参数 logic_junction_id 不能为空！', ERR_PARAMETERS);
        }
        if (empty($params['city_id'])) {
            throw new \Exception('参数 city_id 不能为空！', ERR_PARAMETERS);
        }
        $ret = $this->mapFlowService->getFlows($params);
        $this->response($ret);
    }

    public function editFlow(){
        $params = $this->input->post(null, true);
        $this->validate([
            'logic_junction_id' => 'required|min_length[1]',
            'city_id' => 'required|is_natural_no_zero',
            'version' => 'is_natural_no_zero',
            'logic_flow_id' => 'required|min_length[1]',
            'phase_name' => 'required|min_length[1]',
            'is_hidden' => 'required|is_natural',
        ]);
        $this->mapFlowService->editFlow($params);

        $this->load->model('user/user', 'user');
        $logData = [
            "data"=>$params,
            "user_name"=>$this->user->getUserName(),
            "client_ip"=>$_SERVER["HTTP_X_REAL_IP"]??"",
            "log_time"=>date("Y-m-d H:i:s"),
        ];
        $logFormat = function($logData){
            return sprintf("[INFO] [%s] _editFlow_log||log=%s",date("Y-m-d H:i:s"),json_encode($logData));
        };
        file_put_contents("/home/xiaoju/php7/logs/cloud/itstool/editFlow.log", $logFormat($logData).PHP_EOL, FILE_APPEND);
        
        //操作日志
        $juncNames = $this->waymap_model->getJunctionNames($params["logic_junction_id"]);
        // print_r($juncNames);exit;
        $actionLog = sprintf("路口ID：%s，路口名称：%s，flowID：%s，flow描述：%s，flow状态：%s",$params["logic_junction_id"],implode(",",$juncNames),$params["logic_flow_id"],$params["phase_name"],$params["is_hidden"]?"隐藏":"显示");
        $this->insertLog("flow管理","修改flow描述","编辑",$params,$actionLog);
        $this->response([]);
    }
}
