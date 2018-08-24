<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/24
 * Time: 下午1:08
 */

class TwoDimensionCollection extends Collection
{
    private $xData = [];
    private $yData = [];

    public function setData($data = [])
    {
        parent::setData($data);

        $this->xData = $data;

        foreach ($data as $key => $datum) {
            if(is_array($data)) {

            }
        }
    }
}