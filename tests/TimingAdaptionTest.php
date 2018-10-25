<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/24
 * Time: 上午11:13
 */

namespace Test;


class TimingAdaptionTest extends BaseTestCase
{
    public function testGetAdaptTimingInfo()
    {
        $params = [
            'logic_junction_id' => '2017030116_13174425',
            'city_id' => '12'
        ];

        $uri = '/TimingAdaptation/getAdaptTimingInfo';

        $this->move($uri, $params);
    }

    public function testGetCurrentTimingInfo()
    {
        $params = [
            'logic_junction_id' => '2017030116_13174425',
        ];

        $uri = '/TimingAdaptation/getCurrentTimingInfo';

        $this->move($uri, $params);
    }

    public function testGetCurrentStatus()
    {
        $params = [
            'logic_junction_id' => '2017030116_13174425',
        ];

        $uri = '/TimingAdaptation/getCurrentStatus';

        $this->move($uri, $params);
    }

    public function testGetAdapteStatus()
    {
        $params = [
            'logic_junction_id' => '2017030116_13174425',
            'is_open' => 1
        ];

        $uri = '/TimingAdaptation/getAdapteStatus';

        $this->move($uri, $params);
    }
}