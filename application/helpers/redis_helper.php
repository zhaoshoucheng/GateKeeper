<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!class_exists('RedisEx')) {
    //这里面的方法会抛异常RedisException!!!需要上层自己捕获
    class RedisEx extends Redis
    {
        private $config;
        private $connected_server;

        public function setConfig($config)
        {
            $this->config = $config;
        }

        public function getConfig()
        {
            return $this->config;
        }

        public function connect()
        {
            try {
                if (isset($this->config['socket'])) {
                    $success = parent::connect($this->config['socket']);
                } else {
                    $success = parent::connect($this->config['server']['host'], $this->config['server']['port'], $this->config['timeout']);
                }
                $this->connected_server = "{$this->config['server']['host']}|{$this->config['server']['port']}";
                if (!$success) {
                    //Connection timed out
                    return false;
                }
            } catch (RedisException $e) {
                return false;
            }

            if (isset($config['password']) && (false === parent::auth($config['password']))) {
                return false;
            }
            return true;
        }

        public function setEx($key, $value, $ttl = 60)
        {
            if ($ttl < 0) {
                $ttl = 60;
            }
            try {
                $ret = parent::setEx($key, $ttl, $value);
            }catch (RedisException $ex){
                $ret = false;
            }
            return $ret;
        }

        public function set($key, $value)
        {
            try {
                $ret = parent::set($key, $value);
            }catch (RedisException $ex){
                $ret = false;
            }
            return $ret;
        }

        public function get($key)
        {
            try {
                $ret = parent::get($key);
            }catch (RedisException $ex){
                $ret = false;
            }
            return $ret;
        }

        public function getMultiple($keyArr) {
            try {
                $ret = parent::getMultiple($keyArr);
            }catch (RedisException $ex){
                $ret = false;
            }
            return $ret;
        }

        public function isAvailable()
        {
            try {
                $res = $this->ping();
                if ($res == '+PONG') {
                    return true;
                } else {
                    return false;
                }
            } catch (RedisException $ex) {
                return false;
            }
        }

        public function __call($name, $arguments)
        {
            // TODO: Implement __call() method.
            if(!method_exists(get_parent_class(), $name)){
                return false;
            }
            try {
                $ret = call_user_func_array(array(get_parent_class(),$name), $arguments);
            }catch (RedisException $ex){
                $ret = false;
            }
            return $ret;
        }
    }
}

if (!class_exists('RedisMgr')) {
    class RedisMgr
    {
        private $redisInstance = array();
        private $configs = array();

        private static $mngInstance = null;

        private function __construct()
        {
            $CI =& get_instance();
            if ($CI->config->load('redis', TRUE)) {
                $redis_group = $CI->config->item('redis', 'redis');

                foreach ($redis_group as $name => $conf) {
                    $this->configs[$name] = $conf;
                }

            }
        }

        public static function getInstance($redisIndex)
        {
            if (null == self::$mngInstance) {
                self::$mngInstance = new RedisMgr();
            }
            $ins = self::$mngInstance;
            $redis = null;
            $retry = 0;
            while ($retry++ < 2) {
                if (!isset($ins->redisInstance[$redisIndex])) {
                    $config = $ins->getConfigByIndex($redisIndex);
                    if (!$config) {
                        log_message('error', 'RedisMgr getInstance error, check config', '~');
                        return null;
                    }
                    $redis = new RedisEx();
                    $redis->setConfig($config);
                    if (false === $redis->connect()) {
                        $ins->delUnavailableServer($redisIndex, $config['server']);
                        continue;
                    }
                    $ins->redisInstance[$redisIndex] = $redis;
                    return $redis;
                } else {
                    $redis = $ins->redisInstance[$redisIndex];
                    if (false == $redis->isAvailable()) {
                        $unavailableConfig = $redis->getConfig();
                        $ins->delUnavailableServer($redisIndex, $unavailableConfig['server']);
                        unset($ins->redisInstance[$redisIndex]);
                    } else {
                        return $redis;
                    }
                }
            }
            log_message('error', 'RedisMgr getInstance error, check net or config', '~');
            return null;
        }

        private function getConfigByIndex($index)
        {
            if (!isset($this->configs[$index]) or !isset($this->configs[$index]['servers'])) {
                return false;
            } elseif (count($this->configs[$index]['servers']) == 0) {
                $CI =& get_instance();
                $CI->config->load('redis', TRUE);
                $redis_group = $CI->config->item('redis', 'redis');
                $this->configs[$index] = $redis_group[$index];
            }

            $config = $this->configs[$index];
            $servers = $config['servers'];
            if (empty($servers)) {
                return false;
            }
            shuffle($servers);
            $config['server'] = $servers[0];
            return $config;
        }

        private function delUnavailableServer($index, $delServer)
        {
            $config = $this->configs[$index];
            $servers = $config['servers'];
            $newServers = array();
            foreach ($servers as $value) {
                if ($delServer == $value) {
                    continue;
                }
                $newServers[] = $value;
            }
            $this->configs[$index]['servers'] = $newServers;
        }
    }
}
