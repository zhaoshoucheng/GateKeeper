<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午2:45
 */

namespace Test;


class RoadTest extends BaseTestCase
{
    public function testQueryRoadList()
    {
        $params = [
            'city_id' => 12,
        ];

        $uri = '/road/queryRoadList';

        $this->move($uri, $params);
    }

    public function testGetRoadDetail()
    {
        $params = [
            'city_id' => 12,
            'road_id' => '0e1fe466574ee187795f2fab87f37db4',
        ];

        $uri = '/road/getRoadDetail';

        $this->move($uri, $params);
    }

    public function testGetAllRoadDetail()
    {
        $params = [
            'city_id' => 12,
        ];

        $uri = '/road/getAllRoadDetail';

        $this->move($uri, $params);
    }

    public function testGetQuotas()
    {
        $params = [];

        $uri = '/road/getQuotas';

        $this->move($uri, $params);
    }

    public function testComparison()
    {
        $options = [
            'city_id' => '12',
            'road_id' => 'd00f039b347afff3efb16d872f5a902d',
            'quota_key' => 'time',
            'base_start_date' => '2018-08-01',
            'base_end_date' => '2018-08-02',
            'evaluate_start_date' => '2018-08-02',
            'evaluate_end_date' => '2018-08-04',
            'direction' => '1'
        ];

        $uri = '/road/comparison';

        $this->move($uri, $options);
    }
}