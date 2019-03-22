<?php

namespace Services;

/**
 * Class PermissionService
 * @package Services
 * @property \Upm $Upm
 * @property \User $user
 */
class PermissionService extends BaseService
{
    protected $helperService;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Upm');
        $this->load->model('user/user', 'user');
    }

    public function getUserPermissions()
    {
        $result = $this->Upm->getUserPermissions($this->user->getUserName());
        if (!$result) {
            throw new \Exception('获取用户权限失败', ERR_AUTH_PERMISSION);
        }
        return $result;
    }
}