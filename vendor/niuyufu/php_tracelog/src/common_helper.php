<?php
use Didi\Cloud\TraceLog\TraceLogFactory;

if (!function_exists('operateLog')) {
    /**
     * 记录操作日志
     * @param string $userName      操作人
     * @param string $actionTag     操作action
     * @param int    $itemId        操作对象主键Id
     * @param array  $changeValue   更改内容 var=>val
     * @param array  $extra         附加信息 var=>val
     * @throws Exception
     */
    function operateLog($userName, $actionTag, $itemId, $changeValue, $extra = [])
    {
        $logger = TraceLogFactory::getInstance("operate_log");
        $action = $logger->getAction($actionTag);
        if (!$action) {
            throw new \Exception("请先定义 action.");
        }
        $context["item_id"] = $itemId;
        $context["user_name"] = $userName;
        $context["action_tag"] = $actionTag;
        $context["action_detail"] = $action;
        $context["change_value"] = $changeValue;

        //系统默认
        $context["platform"] = $logger->getPlateform();   //项目名
        $context["create_time"] = date("Y-m-d H:i:s");

        $context = array_merge($extra, $context);
        $logger->info("operate_log", $context);
    }

    /**
     * 初始化操作日志
     * @param string    $path            日志路径
     * @param array     $actionMap       操作action数组
     * @param string    $platform        项目名
     * @param int $userGlobalTraceId     是否使用全局trace_id方法 0=不使用 1=使用
     */
    function initOperateLog($path, $actionMap, $platform="itstool", $userGlobalTraceId = 0)
    {
        TraceLogFactory::getInstance("operate_log", $path, $actionMap, $platform, $userGlobalTraceId);
    }
}
