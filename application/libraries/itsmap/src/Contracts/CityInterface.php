<?php

namespace Didi\Cloud\ItsMap\Contracts;

/*
 * 城市的接口
 */
interface CityInterface
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
    public function all();
}