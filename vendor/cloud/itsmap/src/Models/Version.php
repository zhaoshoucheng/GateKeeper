<?php

namespace Didi\Cloud\ItsMap\Models;

use Didi\Cloud\ItsMap\Exceptions\VersionNotExistError;

/**
 * Class Version
 * @package Didi\Cloud\ItsMap\Models
 */
class Version extends \Illuminate\Database\Eloquent\Model
{
    protected $table = "its_mapdata_version";

    // 单例 mainLand
    private static $mainland;

    const MAX_VERSION = "9999999999";

    /*
     * 存在，未被软删除
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_deleted', '=', 0);
    }

    /*
     * 大陆地区
     */
    public function scopeMainland($query)
    {
        return $query->where('map_type', '=', 0);
    }

    /*
     * 获取所有的版本号，返回array，按照版本排序
     */
    public static function versions()
    {
        if (self::$mainland) {
            return self::$mainland;
        }

        self::$mainland = Version::available()->mainland()->pluck('version_id')->sort()->all();
        return self::$mainland;
    }

    /*
     * 获取最新的版本信息
     */
    public static function newest()
    {
        return max(self::versions());
    }

    /*
     * 获取两个范围之内的所有版本, 开区间，包含 startVersion， 不包含 endVersion
     */
    public static function range($startVersion, $endVersion)
    {
        $versions = self::versions();
        return array_filter($versions, function($version) use ($startVersion, $endVersion) {
            return $version >= $startVersion && $version < $endVersion;
        });
    }


    /*
     * 下一个版本的index
     */
    public static function nextVersion($version)
    {
        $versions = self::versions();
        $key = array_search($version, $versions);
        if ($key === false) {
            throw new VersionNotExistError();
        }

        if ($key == count($versions) - 1) {
            return Version::MAX_VERSION;
        }

        return $versions[$key + 1];
    }

    /*
     * 上一个版本
     */
    public static function preVersion($version)
    {
        $versions = self::versions();
        if ($version == self::MAX_VERSION) {
            return Version::newest();
        }

        $key = array_search($version, $versions);
        if ($key === false) {
            throw new VersionNotExistError();
        }

        if ($key >= 1) {
            return $versions[$key - 1];
        }

        return 0;
    }

    /*
     * 判断versions是否是连续的，且都在里面
     */
    public static function isContinuity(array $versions)
    {
        $allVersions = self::versions();
        $index = array_search($versions[0], $allVersions);

        for ($i = 0; $i < count($versions); $i++) {
            if ($versions[$i] != $allVersions[$index + $i]) {
                return false;
            }
        }
        return true;
    }
}