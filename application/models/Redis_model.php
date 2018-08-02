<?php
/********************************************
# desc:    redis缓存
# author:  ningxiangbing@didichuxing.com
# date:    2018-04-03
********************************************/

class Redis_model extends CI_Model
{
    private $redis = false;

    public function __construct()
    {
        parent::__construct();

        $this->redis = new Redis();
        // 需要设置redis的配置
        $redis_conf = $this->config->item('redis');
        if (isset($redis_conf['host'])) {
            $host = $redis_conf['host'];
            $port = $redis_conf['port'];
            try {
                $this->redis->connect($host, $port);
            } catch (RedisException $e) {
                $this->redis = false;
            }
        }
    }

    /**
    * 获取数据
    * @param $key  string key
    * @return array
    */
    public function getData($key)
    {
        if (!$this->redis) {
            return false;
        }

        try {
            $res = $this->redis->get($key);
            if (!$res) {
                return false;
            }
        } catch  (RedisException $e) {
            $res = false;
        }
        return $res;
    }

    /**
    * 存储数据
    * @param $key    string key
    * @param $value  json   value
    * @return bool
    */
    public function setData($key, $value)
    {
        if (!$this->redis) {
            return false;
        }
        try {
            $res = $this->redis->set($key, $value);
        } catch (RedisException $e) {
            $res = false;
        }
        return $res;
    }

    /**
    * 设置时效
    * @param $key   string   key
    * @param $time  interger 秒
    * @return bool
    */
    public function setExpire($key, $time)
    {
        if (!$this->redis) {
            return false;
        }
        try {
            $this->redis->expire($key, $time);
        } catch (RedisException $e) {
            return false;
        }
        return true;
    }

    /**
    * 删除数据
    * @param $key  string key
    * @return bool
    */
    public function deleteData($key)
    {
        if (!$this->redis) {
            return false;
        }
        try {
            $this->redis->delete($key);
        } catch (RedisException $e) {
            return false;
        }
        return true;
    }

    /**
    * 集合 添加成员
    */
    public function sadd($key, $val)
    {
        if (!$this->redis) {
            return false;
        }
        try {
            $this->redis->sadd($key, $val);
        } catch (RedisException $e) {
            return false;
        }
        return true;
    }

    /**
    * 集合 取成员
    */
    public function smembers($key)
    {
        if (!$this->redis) {
            return [];
        }

        try {
            $res = $this->redis->smembers($key);
        } catch  (RedisException $e) {
            $res = [];
        }
        return $res;
    }

    /**
    * 集合 移除成员
    */
    public function sremData($key, $member)
    {
        if (!$this->redis) {
            return false;
        }

        try {
            $this->redis->srem($key, $member);
        } catch  (RedisException $e) {
            return false;
        }
        return true;
    }

    /**
     * 删除集合
     */
    public function delList($key)
    {
        if (!$this->redis) {
            return false;
        }

        try {
            $this->redis->del($key);
        } catch  (RedisException $e) {
            return false;
        }
        return true;
    }
}
