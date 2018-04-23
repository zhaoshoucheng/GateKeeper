<?php

namespace Didi\Cloud\ItsMap\Services;

require_once __DIR__ . '/../Thrift/RoadNet/ShmDataService.php';
require_once __DIR__ . '/../Thrift/RoadNet/InheritService.php';
require_once __DIR__ . '/../Thrift/RoadNet/Types.php';
require_once __DIR__ . '/../Thrift/StsData/CalculatorService.php';
require_once __DIR__ . '/../Thrift/StsData/Types.php';
require_once __DIR__ . '/../Thrift/Track/MtrajService.php';
require_once __DIR__ . '/../Thrift/Track/Types.php';


use Didi\Cloud\ItsMap\Configs\Thrift;
use Didi\Cloud\ItsMap\Exceptions\ItsMapThriftFormatError;
use Didi\Cloud\ItsMap\Exceptions\ItsMapThriftInnerError;
use Didi\Cloud\ItsMap\Exceptions\ItsMapThriftInoutLinkCountError;
use Didi\Cloud\ItsMap\Exceptions\VersionNotExistError;
use Didi\Cloud\ItsMap\Models\Version;
use DidiRoadNet\NodeIdsReq;
use DidiRoadNet\ResultStatus;
use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TFramedTransport;
use Thrift\Transport\TBufferedTransport;
use Thrift\Transport\TSocket;

use Didi\Cloud\ItsMap\Junction;
use Didi\Cloud\ItsMap\Flow;
use DidiRoadNet\AreaFlowRequest;
use DidiRoadNet\AreaFlowVersionReq;
use DidiRoadNet\LogicJunctionReq;
use DidiRoadNet\LogicFlowReq;
use DidiRoadNet\AreaFlowResponse;

use StsData\RoadVersionRuntime;

use Track\Request as mtraj_request;
use Track\Rtime;
use Track\TypeData;
use Track\FilterData;

/*
 * 导航路网提供的相关继承服务
 */
class RoadNet
{
    private $transport = null;

    private $transportIsOpened = false;

    private $client = null;

    private $service = null;

    private $config = null;

    // 启动 thrift
    public function start($service = "inhert")
    {
        try {
            // Load
            $loader = new ThriftClassLoader();
            $loader->registerNamespace('Thrift', __DIR__ . '/');
            $loader->registerDefinition('RoadNet', __DIR__ . '/../Thrift/RoadNet/');
            $loader->registerDefinition('StsData', __DIR__ . '/../Thrift/StsData/');
            $loader->registerDefinition('Track', __DIR__ . '/../Thrift/Track/');
            $loader->register();

            $this->service = $service;
            $config = Thrift::get($service);
            $this->config = $config;

            // Init
            $socket = new TSocket($config['host'], $config['port']);
            $socket->setDebug(TRUE);
            $transport = new $config['transport']($socket);
            $protocol = new TBinaryProtocol($transport);
            $class = $config['class'];
            $client = new $class($protocol);
            // Config
            $socket->setSendTimeout($config['read_timeout'] * 1000);
            $socket->setRecvTimeout($config['write_timeout'] * 1000);

            // Connect
            $this->transport = $transport;
            $this->client = $client;

            return $this->transport;
        } catch (\Exception $e) {
            Log::notice(json_encode([
                'e' => $e->getMessage(),
            ]));
        }

    }

    // 关闭 thrift
    public function close()
    {
        try {
            if (!empty($this->transport)) {
                $this->transport->close();
            }
        } catch (\Exception $e) {
            Log::notice(json_encode([
                'e' => $e->getMessage(),
            ]));
        }

    }

    public function call($method, $args)
    {
        $retry = 2;

        $response = null;
        while ($retry) {
            try {
                if (false == $this->transportIsOpened) {
                    $this->transport->open();
                }
                $response = call_user_func_array(array($this->client, $method), $args);
                $retry = 0;
            } catch (\Exception $e) {
                Log::notice(json_encode([
                    'config' => $this->config,
                    'method' => $method,
                    'args' => $args,
                    'e' => $e->getMessage(),
                    'retry' => $retry,
                ]));
                $retry--;
                $this->close();
                $this->start($this->service);
                $this->transportIsOpened = false;
                $response = null;
            }
        }

        $config = $this->config;
        Log::notice(json_encode([compact('config', 'method', 'args', 'response', 'retry')]));
        return $response;
    }

    public function checkResponseError($response)
    {
        if ($response->err_no == ResultStatus::WRONG_REQ) {
            throw new ItsMapThriftFormatError($response->err_msg);
        }
        if ($response->err_no == ResultStatus::INTERNAL_ERR) {
            throw new ItsMapThriftInnerError($response->err_msg);
        }
    }

    /*
     * node 继承
     *
     * @params $oldVersion string 被继承的版本
     * @params $nodeIds array(int)
     * @params $newVersions array(string) 要继承的版本列表
     */
    public function nodeInheritProcess($oldVersion, $nodeIds, $newVersions)
    {
        $this->start();

        $request = new \DidiRoadNet\InheritNodesRequest();
        $request->trace_id = uniqid();
        $request->ori_version_id = strval($oldVersion);
        $request->version_ids = array_map('strval', $newVersions);
        $junctionReq = new \DidiRoadNet\JunctionReq();
        $junctionReq->node_id = $nodeIds;
        $request->junc_req = [$junctionReq];

        $response = $this->call('node_inherit_process', [$request]);
        $this->checkResponseError($response);

        // Close
        $this->close();
        return $response;
    }

    /*
     * 生成InoutLink
     */
    public function inoutLinkGenerate($junctionLogicId, $version, $nodeIds)
    {
        $this->start();

        $request = new \DidiRoadNet\InheritInoutLinkRequest();
        $request->trace_id = uniqid();

        $nodeIdsReq = new NodeIdsReq();
        $nodeIdsReq->version_id = strval($version);
        $nodeIdsReq->node_id = $nodeIds;
        $nodeIdsReq->logic_junction_id = $junctionLogicId;

        $request->versions = [$nodeIdsReq];

        $response = $this->call('inout_link_inherit_process', [$request]);
        $this->checkResponseError($response);

        if (count($response->res) != 1) {
            throw new ItsMapThriftInoutLinkCountError();
        }

        $junctionInoutLinkRes = $response->res[0];

        $this->close();
        return $junctionInoutLinkRes;
    }



    /*
     * inout link 的继承
     *
     * @params $versionNodeIds 版本和NodeIds的映射
     * @params $oldVersion 旗帜版本
     * @params $oldNodeIds 旗帜版本的nodeIds
     * @params @oldInoutLinks 旗帜版本的inoutLink array(InoutLinkRes)
     *
     * @return array(JunctionInoutLinkRes)
     */
    public function inoutLinkInhert($logicJunctionId, $versionNodeIds, $oldVersion, $oldNodeIds, $oldInoutRes)
    {
        $this->start();

        $request = new \DidiRoadNet\InheritInoutLinkRequest();
        $request->trace_id = uniqid();
        $junctionId = $logicJunctionId;

        $oldNodeIdsReq = new \DidiRoadNet\NodeIdsReq();
        $oldNodeIdsReq->version_id = strval($oldVersion);
        $oldNodeIdsReq->node_id = $oldNodeIds;
        $oldNodeIdsReq->logic_junction_id = $junctionId;

        $request->ori_version = $oldNodeIdsReq;
        $request->ori_inout_link = [];
        foreach ($oldInoutRes as $inoutRes) {
            $tmp = new \DidiRoadNet\InoutLinkReq();
            $tmp->logic_inout_link_id = $inoutRes->logic_inout_link_id;
            $tmp->link_id = $inoutRes->link_id;
            $tmp->inout_link_flag = $inoutRes->inout_link_flag;
            $request->ori_inout_link[] = $tmp;
        }

        $versions = Version::versions();
        $key = array_search($oldVersion, $versions);
        if ($key === false) {
            throw new VersionNotExistError();
        }

        $oldVersions = array_reverse(array_slice($versions, 0, $key));
        $newVersions = array_slice($versions, $key + 1);

        $oldRequestVersions = [];
        foreach ($oldVersions as $version) {
            if (isset($versionNodeIds[$version])) {
                $nodeIds = $versionNodeIds[$version];
                $nodeIdsReq = new \DidiRoadNet\NodeIdsReq();
                $nodeIdsReq->version_id = strval($version);
                $nodeIdsReq->node_id = $nodeIds;
                $nodeIdsReq->logic_junction_id = $junctionId;
                $oldRequestVersions[] = $nodeIdsReq;
            }
        }

        $newRequestVersions = [];
        foreach ($newVersions as $version) {
            if (isset($versionNodeIds[$version])) {
                $nodeIds = $versionNodeIds[$version];
                $nodeIdsReq = new \DidiRoadNet\NodeIdsReq();
                $nodeIdsReq->version_id = strval($version);
                $nodeIdsReq->node_id = $nodeIds;
                $nodeIdsReq->logic_junction_id = $junctionId;
                $newRequestVersions[] = $nodeIdsReq;
            }
        }

        $ret = [];
        if ($oldRequestVersions) {
            $request->versions = $oldRequestVersions;
            $response = $this->call('inout_link_inherit_process', [$request]);
            $this->checkResponseError($response);

            if (count($response->res) != count($versionNodeIds)) {
                throw new ItsMapThriftInoutLinkCountError();
            }

            foreach ($response->res as $item) {
                $ret[] = $item;
            }
        }

        if ($newRequestVersions) {
            $request->versions = $newRequestVersions;
            $response = $this->call('inout_link_inherit_process', [$request]);
            $this->checkResponseError($response);

            if (count($response->res) != count($versionNodeIds)) {
                throw new ItsMapThriftInoutLinkCountError();
            }

            foreach ($response->res as $item) {
                $ret[] = $item;
            }
        }


        $this->close();
        return $ret;
    }

    /*
     * 查找link信息
     */
    public function linkQuery($version, $linkIds)
    {
        $this->start('shmdata');

        $request = new \DidiRoadNet\LinkAttrRequest();
        $request->version_id = strval($version);
        $request->link_id = $linkIds;
        $request->trace_id = uniqid();

        $response = $this->call('link_attr_query_process', [$request]);
        $this->checkResponseError($response);

        $this->close();
        return $response->res;
    }

    /*
    * 生成全城flow
    */
    public function areaFlowProcess($city_id, $task_id, $trace_id, $hdfs_dir, $versions) {  
        ini_set('memory_limit', '2048M');      
        $areaFlowVersionReq = array();
        foreach ($versions as $version) {
            $junction = new Junction();
            $junctions = array();
            $offset = 0;
            $count = 1000;
            while(true) {
                $tmp = $junction->allWithVersion($city_id, $version, $offset, $count);
                if (empty($tmp)) {
                    break;
                }
                $junctions = array_merge($junctions, $tmp);
                $offset += $count;
            }
            $logicJunctionReq = array();
            $logicFlowReq = array();
            foreach ($junctions as $one) {
                $logic_junction_id = $one['logic_junction_id'];

                $junctionService = new Junction();
                $maps = $junctionService->maps(array($logic_junction_id), $version);
                if (!empty($maps)) {
                    $junctionReq = new LogicJunctionReq();
                    $junctionReq->logic_junction_id = $logic_junction_id;
                    $map = $maps[0];
                    $junctionReq->node_id = explode(',', $map['node_ids']);
                    $logicJunctionReq[] = $junctionReq;
                }

                $flowService = new Flow();
                $flows = $flowService->allByJunction($logic_junction_id, $version);
                if (!empty($flows)) {
                    foreach ($flows as $flow) {
                        $flowReq = new LogicFlowReq();
                        $flowReq->logic_junction_id = $logic_junction_id;
                        $flowReq->logic_flow_id = $flow['logic_flow_id'];
                        $flowReq->in_link_id = strval($flow['inlink']);
                        $flowReq->out_link_id = strval($flow['outlink']);
                        $logicFlowReq[] = $flowReq;
                    }
                }

            }

            
            $req = new AreaFlowVersionReq();
            $req->version_id = $version;
            $req->junction = $logicJunctionReq;
            $req->flow = $logicFlowReq;
            $areaFlowVersionReq[] = $req;
        }

        $areaFlowRequest = new AreaFlowRequest();
        $areaFlowRequest->version_req = $areaFlowVersionReq;
        $areaFlowRequest->task_id = $task_id;
        $areaFlowRequest->trace_id = $trace_id;
        $areaFlowRequest->hdfs_dir = $hdfs_dir;
        // print_r($areaFlowRequest);

        $this->start('inhert_to');

        $response = $this->call('area_flow_process', [$areaFlowRequest]);
        $this->checkResponseError($response);

        $this->close();
        return $response;
    }

    /*
    * 启动计算任务
    */
    public function calculate($city_id, $task_id, $trace_id, $hdfs_dir, $start_time, $end_time, $dateVersion, $timingType) {
        $roadVersionRuntime = array();

        foreach ($dateVersion as $date => $version) {
            $req = new RoadVersionRuntime();
            $req->dateDay = date('Ymd', strtotime($date));
            $req->startTime = $start_time;
            $req->endTime = $end_time;
            $req->roadVersion = $version;
            $roadVersionRuntime[] = $req;
        }
        print_r($roadVersionRuntime);

        $this->start('caculator');

        $response = $this->call('calculate', [$trace_id, $task_id, $city_id, $hdfs_dir, $roadVersionRuntime, $timingType]);

        $this->close();
        if ($response === '' or intval($response) !== 0) {
            throw new \Exceptions();
        }
        return $response;
    }

    /**
    * 获取时空图、散点图
    */
    public function getMtrajData($data, $type) {
        $mtraj_request = new mtraj_request();

        $mtraj_request->junctionId = $data['junctionId'];
        $mtraj_request->flowId = $data['flowId'];
        foreach($data['rtimeVec'] as $v){
            $mtraj_request->rtimeVec[] = new Rtime($v);
        }
        foreach($data['filterData'] as $k=>&$v){
            $v['xData'] = new TypeData($v['xData']);
            $v['yData'] = new TypeData($v['yData']);
            $mtraj_request->filterDataVec[] = new FilterData($v);
        }
        $accessPara = json_encode(['source'=>'signal_pro', 'transMode'=>'thrift', 'userId'=>'web-api']);
        $this->start('mtraj');
        $response = $this->call($type, [$mtraj_request, $accessPara]);
        $this->close();

        return $response;
    }
}
