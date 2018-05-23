<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Didi\Cloud\ItsMap\City;
use Didi\Cloud\ItsMap\Collection as Collection;

use Didi\Cloud\ItsMap\Node;
use Didi\Cloud\ItsMap\Junction;
use Didi\Cloud\ItsMap\MapVersion;
use Didi\Cloud\ItsMap\Services\RoadNet;
use Didi\Cloud\ItsMap\Flow as FlowService;

/*
 * 临时为自适应设置的
 */
class Proxy extends CI_Controller
{
    private $proxyUrl = "100.70.160.62:8000/";

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('http');
    }

    public function html()
    {
        $url = "/star_web_NG_with_server/area_traffic_map_info.html";
        $url = "{$this->proxyUrl}{$url}";

        echo httpGET($url);
        exit;
    }

    public function json()
    {
        $url = "/star_web_NG_with_server/area_traffic_map_info.html";
        $url = "{$this->proxyUrl}{$url}";

        echo httpGET($url);
        exit;
    }

}
