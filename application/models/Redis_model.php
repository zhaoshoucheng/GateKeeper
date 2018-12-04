<?php
/********************************************
 * # desc:    redis缓存
 * # author:  ningxiangbing@didichuxing.com
 * # date:    2018-04-03
 ********************************************/

class Redis_model extends CI_Model
{
    /**
     * @var bool|\Redis
     */
    private $redis = false;

    /**
     * Redis_model constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->redis = new \Redis();
        // 需要设置redis的配置
        $redis_conf = $this->config->item('redis');

        if (isset($redis_conf['host'])) {
            $host       = $redis_conf['host'];
            $port       = $redis_conf['port'];
            $timeout    = $redis_conf['timeout'];  //增加超时时间
            $connResult = $this->redis->connect($host, $port, $timeout);
            if (!$connResult) {
                throw new \Exception("redis connection error:" . json_encode($redis_conf));
            }
        }
    }

    /**
     * 设置时效
     *
     * @param $key   string   key
     * @param $value array|string   value
     * @param $time  int 秒
     *
     * @return bool
     */
    public function setEx($key, $value, $time)
    {
        if (!$this->redis) {
            return false;
        }
        $setResult = false;
        try {
            $setResult = $this->redis->setex($key, $time, $value);
        } catch (\Exception $e) {
            com_log_warning('redis_model_setex_exception', 0, $e->getMessage(), compact("key", "time", "value"));
        }
        if (!$setResult) {
            com_log_warning('redis_model_setex_false', 0, "", compact("key", "time", "value"));
        }
        return true;
    }

    /**
     * 删除数据
     *
     * @param $key  string key
     *
     * @return bool
     */
    public function deleteData($key)
    {
        if (!$this->redis) {
            return false;
        }
        try {
            $this->redis->delete($key);
        } catch (\RedisException $e) {
            return false;
        }
        return true;
    }

    /**
     * 集合 添加成员
     *
     * @param $key
     * @param $val
     *
     * @return bool
     */
    public function sadd($key, $val)
    {
        if (!$this->redis) {
            return false;
        }
        try {
            $this->redis->sadd($key, $val);
        } catch (\RedisException $e) {
            return false;
        }
        return true;
    }

    /**
     * 集合 取成员
     *
     * @param $key
     *
     * @return array
     */
    public function smembers($key)
    {
        if (!$this->redis) {
            return [];
        }

        try {
            $res = $this->redis->smembers($key);
        } catch (\RedisException $e) {
            $res = [];
        }
        return $res;
    }

    /**
     * 集合 移除成员
     *
     * @param $key
     * @param $member
     *
     * @return bool
     */
    public function sremData($key, $member)
    {
        if (!$this->redis) {
            return false;
        }

        try {
            $this->redis->srem($key, $member);
        } catch (\RedisException $e) {
            return false;
        }
        return true;
    }

    /**
     * 删除集合
     *
     * @param $key
     *
     * @return bool
     */
    public function delList($key)
    {
        if (!$this->redis) {
            return false;
        }

        try {
            $this->redis->del($key);
        } catch (\RedisException $e) {
            return false;
        }
        return true;
    }

    /**
     * 获取指定城市的最新 hour
     *
     * @param $cityId
     *
     * @return array|bool|string
     */
    public function getHour($cityId)
    {
        $key = 'its_realtime_lasthour_' . $cityId;

        return $this->getData($key);
    }

    /**
     * 获取数据
     *
     * @param $key  string key
     *
     * @return array|string|bool
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
        } catch (\RedisException $e) {
            $res = false;
        }
        return $res;
    }

    /**
     * 缓存评估数据用于下载
     *
     * @param string $downloadId
     * @param array  $data
     *
     * @return bool
     */
    public function setComparisonDownloadData(string $downloadId, array $data)
    {
        $this->load->config('realtime_conf');

        $key = $this->config->item('quota_evaluate_key_prefix') . $downloadId;

        // 设置Redis缓存
        $d = $this->setData($key, json_encode($data));

        $e = $this->setExpire($key, 30 * 60);

        return $d && $e;
    }

    /**
     * 存储数据
     *
     * @param $key    string key
     * @param $value  array|string value
     *
     * @return bool
     */
    public function setData($key, $value)
    {
        if (!$this->redis) {
            return false;
        }
        try {
            $res = $this->redis->set($key, $value);
        } catch (\RedisException $e) {
            $res = false;
        }
        return $res;
    }

    /**
     * 设置时效
     *
     * @param $key   string   key
     * @param $time  int 秒
     *
     * @return bool
     */
    public function setExpire($key, $time)
    {
        if (!$this->redis) {
            return false;
        }
        try {
            $this->redis->expire($key, $time);
        } catch (\RedisException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param string $downloadId
     *
     * @return bool|mixed
     */
    public function getComparisonDownloadData(string $downloadId)
    {
        $this->load->config('realtime_conf');

        $key = $this->config->item('quota_evaluate_key_prefix') . $downloadId;

        if (!($data = $this->getData($key))) {
            return false;
        }

        return json_decode($data, true);
    }

    /**
     * @param $cityId
     * @param $date
     * @param $hour
     *
     * @return bool|mixed
     */
    public function getRealtimePretreatJunctionList($cityId, $date, $hour)
    {
        $key = "its_realtime_pretreat_junction_list_{$cityId}_{$date}_{$hour}";

        if(!($data = $this->getData($key))) {
            return false;
        }

        return json_decode($data, true);
    }

    /**
     * 平均延误
     * @param $cityId
     * @param $date
     *
     * @return bool|mixed
     */
    public function getRealtimeAvgStopDelay($cityId, $date)
    {
        $key = 'its_realtime_avg_stop_delay_' . $cityId . '_' . $date;

        if(!($data = $this->getData($key))) {
            return false;
        }

        return json_decode($data, true);
    }

    /**
     * @param $token
     *
     * @return bool
     */
    public function setToken($token)
    {
        $d = $this->setData('Token_' . $token, $token);
        $e = $this->setExpire('Token_' . $token, 60 * 30);

        return $d && $e;
    }

    /**
     * @param $token
     *
     * @return bool
     */
    public function verifyToken($token)
    {
        if(!($data = $this->getData($token))) {
            return false;
        }

        $this->deleteData($token);

        return true;
    }

    /**
     * 获取实时报警数据
     * @param $cityId
     * @param $date
     * @param $hour
     *
     * @return bool|mixed
     */
    public function getRealtimeAlarmListByDateHour($cityId,$date,$hour)
    {
        $key = sprintf('its_realtime_alarm_%s_%s_%s', $cityId, $date, $hour);
        if(!($data = $this->getData($key))) {
            return false;
        }

        return json_decode($data, true);
    }

    /**
     * 获取实时报警数据
     * @param $cityId
     *
     * @return bool|mixed
     */
    public function getRealtimeAlarmList($cityId)
    {
        $key = 'its_realtime_alarm_' . $cityId;

        if(!($data = $this->getData($key))) {
            return false;
        }

        return json_decode($data, true);
    }
}
