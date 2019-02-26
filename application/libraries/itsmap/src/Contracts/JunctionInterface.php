<?php

namespace Didi\Cloud\ItsMap\Contracts;

/*
 * 交通路网路口相关接口
 */
interface JunctionInterface
{

    /*
     * 获取一个城市的概要信息
     *
     * @params string $cityId 城市Id
     *
     * @return ['']
     *
     * @throws JunctionNotExistException
     */
    public function summary($cityId);

    /*
     * 根据逻辑Id查找路口信息
     * @params string $logicId 逻辑id
     *
     * @return Models/LogicJunction with Map
     *
     * @throws JunctionNotExistException
     */
    public function find($logicId);

    /*
     * 根据显示Id查找路口信息
     * @params string $showId 显示Id
     *
     * @return Models/LogicJunction with Map
     *
     * @throws JunctionNotExistException
     */
    public function findByShowId($showId);


    /*
     * 显示某个城市，某个路网版本下可用的路口
     * @params int $cityId 城市id
     * @params string $version 路网版本
     *
     * @return array(Models/LogicJunction)
     *
     * @throws
     */
    public function all($cityId, $offset, $count);

    /*
     * 显示某个城市，某个路网版本下可用的路口
     * @params int $cityId 城市id
     * @params string $version 路网版本
     *
     * @return array(Models/LogicJunction)
     *
     * @throws
     */
    public function allWithVersion($cityId, $version, $offset, $count);

    /*
     * 增加路口
     * @params int $cityId 城市id
     * @params array $versionNodes version和nodes的映射关系
     * @params array $attrs 这个路口的基础信息，比如lat，lng
     *
     * @return Models/LogicJunction with Map
     *
     * @throws
     */
    public function add($cityId, $versionNodes, $flagVersion, $attributes = []);

    /*
     * 编辑路口的基础属性
     * @params int $logicId 路口逻辑id
     * @params array(string => string) $attr 属性，key包括['name', 'lat', 'lng', 'is_manual', 'is_traffic']
     *
     * @return Models/LogicJunction with Map
     *
     * @throws JunctionNotExistException
     */
    public function editBase($logicId, array $attr);

    /*
     * 编辑所有路网版本路口的影射关系
     * @params int $logicId 路口逻辑id
     * @params array(string => string) $attr 属性，key包括['name', 'lat', 'lng', 'is_manual', 'is_traffic']
     *
     * @return Models/LogicJunction with Map
     *
     * @throws JunctionNotExistException
     */
    public function editVersionMaps($logicId, $versionNodes, $flagVersion);

    /*
     * 编辑标记版本
     * @params string $logicId 路口逻辑id
     * @params int $flagVersion 路网版本
     *
     * @return Models/LogicJunction with Map
     *
     * @throws JunctionNotExistException
     */
    public function editFlagVersion($logicId, $flagVersion);

    /*
     * 删除这个路口
     * @params int $logicId 路口逻辑id
     *
     * @return boolean
     *
     * @throws JunctionNotExistException
     */
    public function delete($logicId);

    public function findByMainNodeId($mainNodeId, $version);
}