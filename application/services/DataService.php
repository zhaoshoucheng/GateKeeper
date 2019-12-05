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
        $timeout = 10000;

        $url = "{$this->url}{$apiUrl}";
        if ($method == self::METHOD_GET) {
            $ret = httpGET($url, $params, $timeout);
        } else if ($method == self::METHOD_POST) {
            $ret = httpPOST($url, $params, $timeout, $contentType);
        }

        if (!$ret) {
            com_log_warning('dataservice_api_error', ERR_REQUEST_DATASERVICE_API, "dataService错误", compact("url", "method", "params", "timeout", "ret"));
            throw new Exception("dataservice调用失败");
        }

        $ret = json_decode($ret, true);
        if (!$ret) {
            com_log_warning('dataservice_api_error', ERR_REQUEST_DATASERVICE_API, "dataservice错误", compact("url", "method", "params", "timeout", "ret"));
            throw new Exception("dataservice格式错误");
        }

        // 返回的结构体是['errno', 'errmsg', 'data'];
        if (!isset($ret['errno']) || (!isset($ret['errmsg'])) || (!isset($ret['data']))) {
            com_log_warning('dataservice_api_error', ERR_REQUEST_DATASERVICE_API, "dataservice错误", compact("url", "method", "params", "timeout", "ret"));
            throw new Exception("调用dataservice服务返回错误");
        }

        if ($ret['errno'] != 0) {
            com_log_warning('dataservice_api_error', ERR_REQUEST_DATASERVICE_API, "dataservice错误", compact("url", "method", "params", "timeout", "ret"));
            throw new \Exception($ret['errmsg'], $ret['errno']);
        }

        extract($ret);
        return [$errno, $errmsg, $data];
    }
}