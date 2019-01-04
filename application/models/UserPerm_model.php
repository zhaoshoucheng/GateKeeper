<?php

/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2019/1/4
 * Time: 下午1:51
 */
class UserPerm_model extends CI_Model
{
    private $redisPrefix = "";

    /**
     * Area_model constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        // load config
        $this->load->config('nconf');
        $this->redisPrefix = $this->config->item('upm_usergroup_prefix');

        // load model
        $this->load->model('redis_model');
    }

    /**
     * 获取全部用户组
     * @return array()
     */
    public function getUserPermAllGroupid()
    {
        $key = $this->redisPrefix . "_usergroup_app";
        if (!($data = $this->redis_model->getData($key))) {
            com_log_warning('_itstool_' . __CLASS__ . '_' . __FUNCTION__ . '_error', 0, "redis_get_error", compact("key"));
            return [];
        }
        return explode(";", $data);
    }

    /**
     * 获取用户组权限
     * @param $groupId
     * @return array
     */
    public function getPermGroupid($groupId)
    {
        $key = $this->redisPrefix . $groupId;
        if (!($data = $this->redis_model->getData($key))) {
            return [];
        }
        $ret = json_decode($data, true);
        return $ret;
    }

    /**
     * 获取用户组关联的城市id
     * @param $groupId
     * @return array
     */
    public function getCityidByGroup($groupId)
    {
        $perm = $this->getPermGroupid($groupId);
        if(!empty($perm["city_id"])){
            return explode(";",$perm["city_id"]);
        }
        return [];
    }

    /**
     * 获取用户组关联路口id
     * @param $groupId
     * @return array
     */
    public function getJunctionidByGroup($groupId)
    {
        $perm = $this->getPermGroupid($groupId);
        if(!empty($perm["junction_id"])){
            return explode(";",$perm["junction_id"]);
        }
        return [];
    }
}