<?php

class Redis_model extends CI_Model {
    private $redis = false;

    public function __construct() {
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


    public function getData($key) {
        if(!$this->redis){
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

    public function setData($key, $value) {
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

    public function setExpire($key, $time) {
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

    public function deleteData($key) {
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
}
