<?php

namespace Test;

class AreaTest extends BaseTestCase
{
    public function testGetList()
    {
        $params = [
            'city_id' => 12,
        ];

        $uri = '/area/getList';

        $this->move($uri, $params);
    }

    public function testGetAreaJunctionList()
    {
        $params = [
            'area_id' => 15,
        ];

        $uri = '/area/getAreaJunctionList';

        $this->move($uri, $params);
    }

    public function testGetAllAreaJunctionList()
    {
        $params = [
            'city_id' => 12,
        ];

        $uri = '/area/getAllAreaJunctionList';

        $this->move($uri, $params);
    }

    public function testGetQuotas()
    {
        $params = [];

        $uri = '/area/getQuotas';

        $this->move($uri, $params);
    }

    public function testComparison()
    {
        $options = [
            'city_id' => '12',
            'area_id' => '15',
            'quota_key' => 'stop_delay',
            'base_start_date' => '2018-08-01',
            'base_end_date' => '2018-08-05',
            'evaluate_start_date' => '2018-08-06',
            'evaluate_end_date' => '2018-08-11',
        ];

        $uri = '/area/comparison';

        $this->move($uri, $options);
    }
}