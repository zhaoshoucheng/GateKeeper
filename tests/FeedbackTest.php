<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午6:51
 */

namespace Test;


class FeedbackTest extends BaseTestCase
{
    public function testAddFeedback()
    {
        $params = [
            'city_id' => '12',
            'question' => '路口信息为空',
            'type' => '0',
            'desc' => '点击路口无法查看路口信息'
        ];

        $uri = '/feedback/addFeedback';

        $this->move($uri, $params);
    }

    public function tearGetTypes()
    {
        $uri = '/feedback/getTypes';

        $this->move($uri);
    }
}