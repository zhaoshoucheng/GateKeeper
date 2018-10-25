<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/24
 * Time: 下午10:42
 */

namespace Test;


class OverviewTest extends BaseTestCase
{
    public function testJunctionsList()
    {
        $params = [
            'city_id' => 12
        ];

        $uri = '/overview/junctionsList';

        $this->move($uri, $params);
    }

    public function testJunctionSurvey()
    {
        $params = [
            'city_id' => 12
        ];

        $uri = '/overview/junctionSurvey';

        $this->move($uri, $params);
    }

    /**
     *
     */
    public function testOperationCondition()
    {
        $params = [
            'city_id' => 12
        ];

        $uri = '/overview/operationCondition';

        $this->move($uri, $params);
    }

    public function testGetCongestionInfo()
    {
        $params = [
            'city_id' => 12
        ];

        $uri = '/overview/getCongestionInfo';

        $this->move($uri, $params);
    }

    public function testGetToken()
    {
        $uri = '/overview/getToken';

        $data = $this->move($uri);

        return $data[0] ?? '';
    }

    /**
     * @param $token
     * @depends testGetToken
     */
    public function verifyToken($token)
    {
        $params = [
            'tokenval' => $token,
        ];

        $uri = '/overview/verifyToken';

        $data = $this->move($uri, $params);

        $this->assertEquals(true, $data['verify']);
    }

    public function testGetNowDate()
    {
        $uri = '/overview/getNowDate';

        $params = [];

        $this->move($uri, $params);
    }
}