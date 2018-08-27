<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/23
 * Time: 下午4:16
 */

if(!defined('COLLECTION_DIR'))
    define('COLLECTION_DIR', __DIR__ . '/Collection/');

require COLLECTION_DIR .'tools.php';
require COLLECTION_DIR .'Trait/ArrayRawMethod.php';

class Collection
{
    use ArrayRawMethod;

    public function get($key = null)
    {

    }

    private function getByDot($key)
    {

    }
}