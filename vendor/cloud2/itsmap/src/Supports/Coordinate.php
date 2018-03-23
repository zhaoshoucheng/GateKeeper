<?php

namespace Didi\Cloud\ItsMap\Supports;

/*
 * 提供经纬度相关的信息
 */
class Coordinate
{

    /*
     * 对数据库经纬度做10^5的除法
     */
    public static function formatManual($lat)
    {
        return strval(intval($lat) * 1.0 / 100000);
    }

    /*
     * 将经纬度变成数据库需要的
     */
    public static function formatDb($lat)
    {
        return intval(floatval($lat) * 100000);
    }

    /*
     * 返回几何经纬度信息
     *
     * @params $latLngs array 数组，lat和lng为key
     *
     * @return [lat, lng] 返回数组，lat和lng代表几何经纬度
     */
    public static function geometric($latLngs)
    {
        $latSum = array_reduce($latLngs, function($carry, $item) {
            return $carry + $item['lat'];
        });

        $lngSum = array_reduce($latLngs, function($carry, $item) {
            return $carry + $item['lng'];
        });

        return ['lat' => $latSum * 1.0 / count($latLngs),  'lng' => $lngSum * 1.0 / count($latLngs)];
    }

    /*
     * 获取两个经纬度之间的距离
     */
    public static function distance($lat1, $lng1, $lat2, $lng2)
    {
        $radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1-$radLat2;
        $b = $radLng1-$radLng2;
        $s = 2*asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137*1000;
        return $s;
    }

    /*
     * 获取gradIndex的相邻9个的id
     */
    public static function gridIndex100($lat, $lng)
    {
        return self::gridIndex($lat, $lng, 100);
    }

    /*
     * 获取gridIndex
     */
    public static function gridIndex($lat, $lng, $size = 100)
    {
        $lngLatOffset = 100000;
        $lngOffset = 100000 * 1000;

        $xIdx = intval($lng * $lngLatOffset / $size);
        $yIdx = intval($lat * $lngLatOffset / $size);

        $ret = [];
        for ($i = 0; $i < 9; $i++) {
            $ret[] = self::getGridId($xIdx - 1 + $i % 3, $yIdx - 1 + intval($i / 3), $size, $lngOffset);
        }
        return $ret;
    }

    private static function getGridId($xIdx, $yIdx, $size, $lngOffset)
    {
        $val = $xIdx * $size * $lngOffset + $yIdx * $size;
        return sprintf ("%.0f", $val);
    }

    public static function getGridIdByCoordinate($lat, $lng, $size = 1000)
    {
        $lngLatOffset = 100000;
        $lngOffset = 100000 * 1000;

        $xIdx = intval($lng * $lngLatOffset / $size);
        $yIdx = intval($lat * $lngLatOffset / $size);

        return self::getGridId($xIdx, $yIdx, $size, $lngOffset);
    }
}