<?php

namespace Didi\Cloud\ItsMap\Contracts;

/*
 * 交通路网对应的新四的Node相关接口
 */
interface NodeInterface
{
    /*
     * 获取某个版本的nodes信息
     *
     * @params array $nodeIds, node信息
     * @params int $version 路网版本
     */
    public function nodes($nodeIds, $version);

    /*
     * 获取某个城市某个版本某个坐标点某个范围内的nodes
     * @params int $cityId 城市id
     * @params int $version 路网版本
     * @params int $lat 纬度*10^5
     * @params int $lng 经度*10^5
     * @params int $distance 坐标点范围,单位：米
     *
     * @return array(Models/Node)
     *
     * @throws
     */
    public function regionNodes($cityId, $version, $lat, $lng, $distance);

    /*
     * 获取所有子点
     *
     * @params int $mainNodeId 主点id
     * @params int $version 路网版本
     *
     * @return array(Models/Node)
     *
     * @throws
     */
    public function subNodes($mainNodeId, $version);

    /*
     * 获取主点
     *
     * @params int $nodeId 子点id
     * @params int $version 路网版本
     *
     * @return array(Models/Node)
     *
     * @throws
     */
    public function mainNode($nodeId, $version);

    /*
     * 获取新四路网的所有版本列表
     *
     * @return array(String)
     */
    public function versions();

    /*
     * 返回继承建议
     */
    public function suggest($cityId, $version, $nodeIds, $toVersions);

}