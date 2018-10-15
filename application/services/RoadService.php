<?php
/**
 * 信控平台 - 干线相关接口
 *
 * User: lichaoxi_i@didiglobal.com
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class RoadService
 * @package Services
 *
 * @property \Waymap_model $waymap_model
 * @property \Redis_model $redis_model
 * @property \Road_model $road_model
*/
class RoadService extends BaseService
{
    /**
     * RoadService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('waymap_model');
        $this->load->model('redis_model');
        $this->load->model('road_model');

        $this->load->config('evaluate_conf');
    }


}