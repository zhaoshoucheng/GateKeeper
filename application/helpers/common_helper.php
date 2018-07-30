<?php

if (!function_exists('operateLog')) {
    /**
     * 记录操作日志
     * @param $userName string      当前用户名
     * @param $actionTag string     当前操作tag
     * @param $itemId   string      当前操作主键Id
     * @param $changeValue string   当前操作变更内容
     * @param array $extra array    附加内容
     * @throws Exception
     */
    function operateLog($userName, $actionTag, $itemId, $changeValue, $extra = [])
    {
        //请先自定义action, 否则会抛出异常.
        $actionMap = [
            'adapt_area_switch_edit' => '自适应区域配时开关修改',
        ];
        if(empty($actionMap[$actionTag])){
            throw new \Exception("请先定义 action.");
        }

        $logger = TraceLog::getInstance("operate_log", "");
        $context["item_id"] = $itemId;
        $context["user_name"] = $userName;
        $context["action_tag"] = $actionTag;
        $context["action_detail"] = $actionMap[$actionTag];
        $context["change_value"] = $changeValue;

        //系统默认
        $context["platform"] = "itstool";   //项目名
        $context["create_time"] = date("Y-m-d H:i:s");

        $context = array_merge($extra, $context);
        $logger->info("operate_log", $context);
    }
}
