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
        \Didi\Cloud\ItsMap\User::register($this->username);
        $logger = & load_class('Log', 'core');
        \Didi\Cloud\ItsMap\Services\Log::registerLogger($logger);
    }


    public function findSimpleByMainNode()
    {
        $version = $this->input->get('version');
        $mainNodeId = $this->input->get('main_node_id');

        try {
            $junctionService = new Junction();
            $this->outputData = $junctionService->findByMainNodeId($mainNodeId, $version);
        } catch (\Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
    }

    public function findSimpleFlowByInoutLink()
    {
        $version = $this->input->get('version');
        $mainNodeId = $this->input->get('main_node_id');
        $inLink = $this->input->get('in_link');
        $outLink = $this->input->get('out_link');

        try {
            $flowService = new FlowService();
            $this->outputData = $flowService->findByMainNodeIdInoutLink($mainNodeId, $inLink, $outLink, $version);
        } catch (\Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
    }

    public function flowsByJunction()
    {
        $version = $this->input->get('version');
        $logicJunctionId = $this->input->get('logic_junction_id');

        try {
            $flowService = new FlowService();
            $flows = $flowService->allByJunction($logicJunctionId, $version);
            $this->outputData = $flows;
        } catch (\Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
    }

    public function flowsByJunctions()
    {
        $version = $this->input->get('version');
        $logicJunctionIds = $this->input->get('logic_junction_ids');
        $logicJunctionIds = explode(',', $logicJunctionIds);

        try {
            $flowService = new FlowService();
            $flows = $flowService->allByJunctions($logicJunctionIds, $version);
            $this->outputData = $flows;
        } catch (\Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
    }

    public function findComplexFlowByInoutLink()
    {
        $version = $this->input->get('version');
        $logicJunctionId = $this->input->get('logic_junction_id');
        $inLink = $this->input->get('in_link');
        $outLink = $this->input->get('out_link');

        try {
            $flowService = new FlowService();
            $flows = $flowService->allByJunction($logicJunctionId, $version);
            foreach ($flows as $flow) {
                if ($flow['inlink'] == $inLink && $flow['outlink'] == $outLink) {
                    $this->outputData = $flow;
                    return;
                }
            }
        } catch (\Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
    }

    public function allByJunctionWithLinkAttr()
    {
        $version = $this->input->get('version');
        $logicJunctionId = $this->input->get('logic_junction_id');

        try {
            $flowService = new FlowService();
            $this->outputData = $flowService->allByJunctionWithLinkAttr($logicJunctionId, $version);
        } catch (\Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
    }

    public function simplifyFlows()
    {
        $logic_junction_id = $this->input->get_post('logic_junction_id');
        $version = $this->input->get_post('version');
        $logic_flow_ids = $this->input->get_post('logic_flow_ids');
        try {
            $flowService = new FlowService();
            $this->output_data = $flowService->simplifyFlows($logic_junction_id, $version, $logic_flow_ids);
        } catch (Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
    }
}
