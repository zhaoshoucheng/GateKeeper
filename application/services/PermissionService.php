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

    public function getUserMenus()
    {
        $menuid = $this->config->item('menuid');
        $result = $this->Upm->getUserMenus($this->user->getUserName());
        if (!$result) {
            return [];
        }
        $menuList = $this->getSubUserMenus($menuid,$result,"signal");
        return $menuList;
    }

    private function getSubUserMenus($pid,$menuList,$remark=""){
        $currentMenuList = [];
        foreach ($menuList as $item){
            if($item["pid"]==$pid){
                $formatItem = [
                    "name"=>$item["name"],
                    "url"=>$item["url"],
                    "remark"=>$remark,
                ];
                $subList = $this->getSubUserMenus($item["id"],$menuList);
                if(!empty($subList)){
                    $formatItem["son"] = $subList;
                }
                $currentMenuList[] = $formatItem;
            }
        }
        return $currentMenuList;
    }

    public function getUserPermissions()
    {
        $result = $this->Upm->getUserPermissions($this->user->getUserName());
        if (!$result) {
            //throw new \Exception('获取用户权限失败', ERR_AUTH_PERMISSION);
        }
        return $result;
    }

    public function hasPermissionByFlag($flag)
    {
        return $this->Upm->hasPermissionByFlag($this->user->getUserName(),$flag);
    }
}