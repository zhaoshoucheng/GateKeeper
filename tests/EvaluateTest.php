<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/22
 * Time: 下午9:09
 */

namespace Test;


class EvaluateTest extends BaseTestCase
{
    public function testGetCityJunctionList()
    {
        $params = [
            'city_id' => 12,
        ];

        $uri = '/evaluate/getCityJunctionList';

        $this->move($uri, $params);
    }

    public function testGetQuotaList()
    {
        $params = [
            'city_id' => 12,
        ];

        $uri = '/evaluate/getQuotaList';

        $this->move($uri, $params);
    }

    public function testGetDirectionList()
    {
        $params = [
            'city_id' => '12',
            'junction_id' => 'fd7ec865170ffd95d8012edb8de2042f',
        ];

        $uri = '/evaluate/getDirectionList';
    }

    public function testGetJunctionQuotaSortList()
    {
        $params = [
            'city_id' => '12',
            'quota_key' => 'stop_delay',
        ];

        $uri = '/evaluate/getJunctionQuotaSortList';

        $this->move($uri, $params);
    }

    public function testGetQuotaTrend()
    {
        $params = [
            'city_id' => '12',
            'junction_id' => 'fd7ec865170ffd95d8012edb8de2042f',
            'quota_key' => 'stop_delay',
            'flow_id' => 'e57af83a3cd91c579a89481dd3a09f80',
        ];

        $uri = '/evaluate/getQuotaTrend';

        $this->move($uri, $params);
    }

    public function testQuotaEvaluateCompare()
    {
        $params = array(
            'city_id' => '12',
            'junction_id' => 'fd7ec865170ffd95d8012edb8de2042f',
            'quota_key' => 'stop_delay',
            'flow_id' => '9999'
        );

        $uri = '/evaluate/quotaEvaluateCompare';

        $this->move($uri, $params);
    }
}