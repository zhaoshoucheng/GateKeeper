<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/5/14
 * Time: 下午4:18
 */


//抽象工厂类
interface EvaluateQuotaFactory{
    public function load_data($data);
}