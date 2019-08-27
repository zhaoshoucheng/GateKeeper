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
        $this->response([]);
    }
}
