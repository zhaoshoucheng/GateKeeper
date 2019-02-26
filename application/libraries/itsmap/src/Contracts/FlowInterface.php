<?php

namespace Didi\Cloud\ItsMap\Contracts;

/*
 * flow相关的接口
 */
interface FlowInterface
{

    /*
     * 根据flowId和版本获取flow 信息
     */
    public function find($flowId, $version);

    /*
     * 获取一个路口的所有Flow
     */
    public function allByJunction($junctionId, $version);

    /*
     * 从新四的inLink 和 outLink 查找 Flow
     */
    public function findFlowByInoutLink($inLink, $outLink, $version);

    /*
     * 根据新四的 inLink 和 outLink 对查找Flow，批量查找
     */
    public function findFlowsByInoutLink($inOutLinkPairs, $version);

}