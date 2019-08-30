<?php
/**
 * 信控平台 - 区域相关接口
 *
 * User: lichaoxi_i@didichuxing.com
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class MapFlowService
 * @package Services
 * @property \waymap_model $waymap_model
 */
class MapFlowService extends BaseService
{
    /**
     * MapFlowService constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('waymap_model');
        $this->load->config('nconf');
    }

    public function getFlows($params){
        $center = $this->waymap_model->getJunctionCenterCoords($params['logic_junction_id']);
        $flowInfos = $this->waymap_model->getFlowsInfo32(
            $params['logic_junction_id'],
            0,
            $with_hidden=1); 
        $flows = $flowInfos[$params['logic_junction_id']] ?? [];
        $version = $flowInfos["version"] ?? "";
        return ["list"=>$flows,"version"=>$version,"center"=>$center];
    }

    public function editFlow($params){
        $res = $this->waymap_model->saveFlowInfo(
            $params['logic_junction_id'],
            $params['logic_flow_id'],
            $params['phase_name'],
            $params['is_hidden']); 
        return $res;
    }
}