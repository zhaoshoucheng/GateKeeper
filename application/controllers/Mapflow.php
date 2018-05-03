<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Didi\Cloud\ItsMap\City;
use Didi\Cloud\ItsMap\Collection as Collection;

use Didi\Cloud\ItsMap\Node;
use Didi\Cloud\ItsMap\Junction;
use Didi\Cloud\ItsMap\MapVersion;
use Didi\Cloud\ItsMap\Services\RoadNet;
use Didi\Cloud\ItsMap\Flow as FlowService;

class Mapflow extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('http');
        $this->load->config('nconf');
    }

    public function simplifyFlows()
    {
        $logic_junction_id = $this->input->get_post('logic_junction_id');
        $version = $this->input->get_post('version');
        $logic_flow_ids = $this->input->get_post('logic_flow_ids');
        try {
            $data = [
                        'logic_junction_id' => $logic_junction_id,
                        'version' => $version,
                        'logic_flow_ids' => $logic_flow_ids,
                        'token'     => $this->config->item('waymap_token'),
                        'user_id'   => $this->config->item('waymap_userid'),
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
}
