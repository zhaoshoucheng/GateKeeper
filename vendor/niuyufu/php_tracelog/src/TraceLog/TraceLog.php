<?php
namespace Didi\Cloud\TraceLog;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Created by PhpStorm.
 * Date: 18/7/26
 * Time: 下午3:01
 */

/**
 * Class TraceLog
 *
 * 一、使用方法
 * $logger = new TraceLog("your.log");
 * $logger->Info(111,["name"=>"hello_name"]);
 * 查看方法
 * tail -f your.log.inf
 *
 * 二、traceid,spanceid,cspanid逻辑关系请查看
 * http://wiki.intra.xiaojukeji.com/pages/viewpage.action?pageId=34953919
 *
 * 三、curl请求资源时,请看这里
 * 1、注意的设置header值信息如下:
 * HTTP_DIDI_HEADER_RID==traceid
 * HTTP_DIDI_HEADER_SPANID==cspanid
 *
 * 2、设置cspanid信息
 * $context['cspanid'] = gen_span_id();
 */
class TraceLog
{
    private static $loggerMap;
    private $logger;
    private $_global_log_id = 0;
    private $_global_trace_id = 0;
    private $_global_span_id = 0;
    private $_global_parent_span_id = 0;
    private $useGlobalTraceId = 0;  //1 使用全局trace_id

    function __construct($name = "default", $path, $userGlobalTraceId=0)
    {
        $this->logger = new Logger($name);
        $this->setPushHandler($path);
        $this->setPushProcessor();
        $this->useGlobalTraceId = $userGlobalTraceId;

        self::$loggerMap[$name] = $this;
    }

    public static function getInstance($name, $path, $userGlobalTraceId=0)
    {
        if (isset(self::$loggerMap[$name])) {
            return self::$loggerMap[$name];
        }
        return new TraceLog($name, $path, $userGlobalTraceId=0);
    }

    public function info($message, $context = array(), $dltag = "_undef")
    {
        $context['dltag'] = $dltag;
        $this->logger->info($message, $context);
    }

    public function error($message, $context = array(), $dltag = "_undef")
    {
        $context['dltag'] = $dltag;
        $this->logger->error($message, $context);
    }

    public function warning($message, $context = array(), $dltag = "_undef")
    {
        $context['dltag'] = $dltag;
        $this->logger->warning($message, $context);
    }

    //设置写入handler及formatter
    public function setPushHandler($path = 'your.log')
    {
        $dateFormat = "Y-m-d\TH:i:s.000P";
        $output = "[%level_name%][%datetime%][%message%] %dltag%||%context%\n";
        $formatter = new TraceFormatter($output, $dateFormat);

        $stream = new StreamHandler($path . ".inf", Logger::INFO);
        $stream->setFormatter($formatter);
        $this->logger->pushHandler($stream);

        $stream = new StreamHandler($path . ".wf", Logger::WARNING);
        $stream->setFormatter($formatter);
        $this->logger->pushHandler($stream);

        $stream = new StreamHandler($path . ".err", Logger::ERROR);
        $stream->setFormatter($formatter);
        $this->logger->pushHandler($stream);
    }

    //设置关联信息
    public function setPushProcessor()
    {
        $this->logger->pushProcessor(function ($record) {
            $record['extra']['args'] = ($_REQUEST);
            $record['extra']['logid'] = $this->useGlobalTraceId ?
                gen_logid() : $this->gen_logid();
            $record['extra']['traceid'] = $this->useGlobalTraceId ?
                gen_traceid() : $this->gen_traceid();
            if ($this->t_get_parent_span_id() == "0000000000000000") {
                $record['extra']['spanid'] = $this->useGlobalTraceId ?
                    t_get_span_id() : $this->t_get_span_id();
            } else {
                $record['extra']['spanid'] = $this->useGlobalTraceId ?
                    t_get_parent_span_id() : $this->t_get_parent_span_id();
            }
            return $record;
        });
        $this->logger->pushProcessor(new \Monolog\Processor\WebProcessor());
        $this->logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor(Logger::DEBUG, array(), 1));
    }

    private function gen_logid()
    {
        if ($this->_global_log_id != 0) {
            return $this->_global_log_id;
        }
        if (isset($_SERVER['HTTP_CLIENTAPPID'])) {
            //client 传入了id则直接使用
            //转换成数字，否则后端使用可能有问题
            $this->_global_log_id = intval($_SERVER['HTTP_CLIENTAPPID']);
            $this->_global_log_id *= 100; //末尾两位用于累计对后端的调用过程
            return;
        }
        //通过ip和当前时间算一个id
        $reqip = '127.0.0.1';
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $reqip = $_SERVER['REMOTE_ADDR'];
        } elseif (isset($_SERVER['SERVER_ADDR'])) {
            $reqip = $_SERVER['SERVER_ADDR'];
        }
        $time = gettimeofday();
        $time = $time['sec'] * 100 + $time['usec'];
        $ip = ip2long($reqip);
        $this->_global_log_id = ($time ^ $ip) & 0xFFFFFFFF;
        $this->_global_log_id *= 100;
        return $this->_global_log_id;
    }

    private function gen_traceid()
    {
        if ($this->_global_trace_id != 0) {
            return $this->_global_trace_id;
        }
        if (isset($_SERVER['HTTP_DIDI_HEADER_RID'])) {
            $this->_global_trace_id = $_SERVER['HTTP_DIDI_HEADER_RID'];
            return $this->_global_trace_id;
        }

        $uuid = sprintf(
            '%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $this->_global_trace_id = $uuid;
        return $this->_global_trace_id;
    }

    private function t_get_parent_span_id()
    {
        if ($this->_global_parent_span_id != '') {
            return $this->_global_parent_span_id;
        }
        if (isset($_SERVER['HTTP_DIDI_HEADER_SPANID'])) {
            $this->_global_parent_span_id = $_SERVER['HTTP_DIDI_HEADER_SPANID'];
            return $this->_global_parent_span_id;
        }
        $this->_global_parent_span_id = sprintf('%016s', 0);
        return $this->_global_parent_span_id;
    }

    private function t_get_span_id()
    {
        if ($this->_global_span_id != '') {
            return $this->_global_span_id;
        }
        $this->_global_span_id = $this->t_gen_random_id();
        return $this->_global_span_id;
    }

    private function t_gen_random_id()
    {
        $reqip = '127.0.0.1';
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $reqip = $_SERVER['REMOTE_ADDR'];
        } elseif (isset($_SERVER['SERVER_ADDR'])) {
            $reqip = $_SERVER['SERVER_ADDR'];
        }
        $time = gettimeofday();
        $time = $time['sec'] + $time['usec'];
        $rand = mt_rand();
        $ip = ip2long($reqip);
        $random_id = $this->t_id_to_hex($ip ^ $time) . "" . $this->t_id_to_hex($rand);
        return $random_id;
    }

    private function gen_span_id()
    {
        return t_gen_random_id();
    }


    /**
     * int to hex string
     */
    private function t_id_to_hex($id){
        return sprintf('%08s',dechex($id));
    }
}
