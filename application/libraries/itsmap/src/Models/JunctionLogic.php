<?php

namespace Didi\Cloud\ItsMap\Models;

use Didi\Cloud\ItsMap\Exceptions\Exception;
use Didi\Cloud\ItsMap\Exceptions\NodeNotExistException;
use Didi\Cloud\ItsMap\Junction;
use Didi\Cloud\ItsMap\Services\RoadNet;
use Didi\Cloud\ItsMap\Supports\Coordinate;
use Didi\Cloud\ItsMap\Supports\DiffTrait;
use Illuminate\Database\Eloquent\Collection;

class JunctionLogic extends \Illuminate\Database\Eloquent\Model
{
    use DiffTrait;

    protected $table = "junction_logic";

    protected function uniqFields()
    {
        return ['logic_junction_id'];
    }

    protected function updateFields()
    {
        return ['name_manual', 'lng', 'lat', 'grid_index_1000', 'city_id', 'is_manual', 'is_traffic', 'is_complex', 'is_deleted', 'start_version', 'end_version'];
    }

    /*
     * 生成复杂路口的LogicId
     */
    public static function genManualLogicId($nodeIds, $version)
    {
        sort($nodeIds);
        return md5("{$version}_" . implode(",", $nodeIds));
    }

    /*
     * 生成ShowId
     */
    public static function genShowId($logicId)
    {
        return md5($logicId . "its");
    }

    /*
     * 增加一个lat_real属性
     */
    public function getLatRealAttribute()
    {
        return Coordinate::formatManual($this->lat);
    }

    /*
     * 增加一个lng_real属性
     */
    public function getLngRealAttribute()
    {
        return Coordinate::formatManual($this->lng);
    }

    /*
     * 和JunctionLogicMap的关联关系
     */
    public function maps()
    {
        return $this->hasMany(JunctionLogicMap::class, 'logic_junction_id', 'logic_junction_id');
    }

    /*
     * 和JunctionLogicEx的关联关系
     */
    public function logicEx()
    {
        return $this->hasOne(JunctionLogicEx::class, 'logic_junction_id', 'logic_junction_id');
    }

    /*
     * 把$this->name做成逻辑
     */
    public function getNameAttribute()
    {
        if (!empty($this->name_manual)) {
            return $this->name_manual;
        }
        return $this->name_auto;
    }

    /*
     * 是否可用的scope
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_deleted', '=', 0);
    }

    /*
     * 是否符合版本
     */
    public function scopeVersionInRange($query, $version)
    {
        return $query->where('start_version', "<=", $version)->where('end_version', '>', $version);
    }

    public function toArray()
    {
        return [
            'logic_junction_id' => $this->logic_junction_id,
            'logic_show_id' => $this->logic_show_id,
            'lng' => Coordinate::formatManual($this->lng),
            'lat' => Coordinate::formatManual($this->lat),
            'city_id' => $this->city_id,
            'name' => $this->name,
            'is_manual' => boolval($this->is_manual),
            'is_traffic' => boolval($this->is_traffic),
            'is_complex' => boolval($this->is_complex),
            'created_at' => strval($this->created_at),
            'updated_at' => strval($this->updated_at),
        ];
    }

    /*
     * 返回结果，有map的返回结果
     */
    public function toArrayWithMap()
    {
        if (empty($this->maps)) {
            $this->load('maps');
        }

        if (empty($this->logicEx)) {
            $this->load('logicEx');
        }

        $logicArr =  $this->toArray();

        $logicArr['flag_version'] = 0;
        if (!empty($this->logicEx)) {
            $logicArr['flag_version'] = $this->logicEx->version;
        }

        $versionMaps = JunctionLogicMap::toAllVersionMaps($this->maps);
        $logicArr['versionMaps'] = $versionMaps;

        return $logicArr;
    }

    /*
     * 新生成Junction
     */
    public static function newJunction($version, $nodeIds, $cityId, $attributes = [])
    {
        // 根据versionNodes判断它的主子点关系
        $nodeModel = new Node();
        $versionNodes = $nodeModel->versionNodes([$version => $nodeIds]);
        $nodes = $versionNodes[$version];

        $isSimpleJunction = false;
        $mainNodeIds = $nodes->pluck('main_node_id')->unique()->all();
        if (count($mainNodeIds) == 1) {
            $isSimpleJunction = true;
        }

        // 生成LogicId
        $logicId = JunctionLogic::genManualLogicId($nodeIds, $version);

        $junctionLogic = new JunctionLogic();
        $junctionLogic->logic_junction_id = $logicId;
        $junctionLogic->logic_show_id = JunctionLogic::genShowId($logicId);
        // 获取经纬度, 使用最新版本的路口mainNode的经纬度
        $latLng = $nodes->map(function($node) {return ['lat' => Coordinate::formatManual($node->lat), 'lng' => Coordinate::formatManual($node->lng)] ; })->all();
        list($lat, $lng) = array_values(Coordinate::geometric($latLng));
        $junctionLogic->city_id = $cityId;
        $junctionLogic->is_manual = 1;
        $junctionLogic->is_traffic = 1; // 默认手动添加的点是有红绿灯的。
        $junctionLogic->is_complex = !$isSimpleJunction;
        $junctionLogic->lat = Coordinate::formatDb($lat);
        $junctionLogic->lng = Coordinate::formatDb($lng);
        $junctionLogic->grid_index_1000 = Coordinate::getGridIdByCoordinate($lat, $lng, 1000);
        $junctionLogic->name_manual = empty($attributes['name']) ?  '' : $attributes['name'];

        return $junctionLogic;
    }


}