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

    /*
     * 自适应
     */
    public function zsy()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $realUri = str_replace('/signalpro/proxy/zsy/', '/', $requestUri);
        $realUri = "{$this->proxyUrl}{$realUri}";

        if (strpos($realUri, "..")) {
            echo "invalid path";
            exit;
        }

        if (strpos($realUri, ".css")) {
            header("Content-type:text/css");
        }

        echo httpGET($realUri);
        exit;
    }

}
