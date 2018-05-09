<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!class_exists('RedisEx')) {
    //这里面的方法会抛异常RedisException!!!需要上层自己捕获
    class RedisEx
    {
        private $config;
        private $connected_server;
        private $redisInstance;

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
                $this->redisInstance = new Redis();
                if (isset($this->config['socket'])) {
                    $this->redisInstance->connect($this->config['socket']);
                } else {
                    $this->redisInstance->connect($this->config['server']['host'], $this->config['server']['port'], $this->config['timeout']);
                }
                $this->connected_server = "{$this->config['server']['host']}|{$this->config['server']['port']}";

            } catch (RedisException $e) {
                return false;
            }

            if (isset($config['password']) && (false === $this->redisInstance->auth($config['password']))) {
                return false;
            }
            return true;
        }

        public function isAvailable()
        {
            try {
                $res = $this->redisInstance->ping();
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
            $start_time = microtime(TRUE);
            try {
                $ret = call_user_func_array(array($this->redisInstance, $name), $arguments);

                $proc_time = microtime(TRUE) - $start_time;
                com_log_strace('_com_redis_success', array("host"=>$this->config['server']['host'], "port"=>$this->config['server']['port'], "method"=>$name, "args"=>json_encode($arguments), "result"=>$ret, 'proc_time'=>$proc_time));

            }catch (RedisException $ex){
                $ret = false;

                com_log_warning('_com_redis_failure', '', 'Redis call_fail', array('method'=>$name, 'args'=>$arguments));
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
