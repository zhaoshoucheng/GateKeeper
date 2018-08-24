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

    const SINGLE = 1;
    const MULTIPLE = 2;

    public function setData($data = [])
    {
        parent::setData($data);

        $this->setXData();
        $this->setYData();

        return $this;
    }

    public function getXKeys()
    {
        return array_keys($this->getXData());
    }

    public function getYKeys()
    {
        return array_keys($this->getYData());
    }

    public function getXData()
    {
        return $this->xData;
    }

    public function getYData()
    {
        return $this->yData;
    }

    public function toLineChart()
    {
        $data = [];

        foreach ($this->getXData() as $XDatum) {

        }
    }

    protected function setYData()
    {
        $this->yData = parent::toArray();
        return $this;
    }
    protected function setXData()
    {
        $this->xData = [];
        foreach (parent::toArray() as $key => $datum) {
            if(is_array($datum)) {
                foreach ($datum as $k => $v) {
                    $this->xData[$k][$key] = $v;
                }
            }
        }
        return $this;
    }
}