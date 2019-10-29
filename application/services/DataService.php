<?php
/**
 * dataService
 *
 */

namespace Services;

use Didi\Cloud\Collection\Collection;
use Didi\Cloud\ItsMap\Exceptions\Exception;

/**
 * Class DataService，调用数据服务的接口
 * @package Services
 * @property \Area_model $area_model
 */
class DataService extends BaseService
{

    private $url;

    public function __construct()
    {
        parent::__construct();
        $this->url = $this->config->item('data_service_interface');
    }

    const METHOD_GET = "GET";
    const METHOD_POST = "POST";

    const ApiFlowDetailQuota = "/quota/flow";

    // 通用调用api的服务, 进行远程调用
    // 错误信息通过Exception形式抛出
    public function call($apiUrl, $params, $method = "GET", $contentType='x-www-form-urlencoded')
    {
        $timeout = 1000;

        $url = "{$this->url}{$apiUrl}";
        if ($method == self::METHOD_GET) {
            $ret = httpGET($url, $params, $timeout);
        } else if ($method == self::METHOD_POST) {
            $ret = httpPOST("{$this->url}{$apiUrl}", $params, $timeout, $contentType);
        }

        $ret = json_decode($ret, true);
        // 返回的结构体是['errno', 'errmsg', 'data'];
        if (!isset($ret['errno']) || (!isset($ret['errmsg'])) || (!isset($ret['data']))) {
            throw new Exception("调用dataService服务返回错误");
        }

        extract($ret);
        return [$errno, $errmsg, $data];
    }
}