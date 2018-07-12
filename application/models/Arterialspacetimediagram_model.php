<?php
/********************************************
# desc:    干线时空图数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-06-29
********************************************/
use Didi\Cloud\ItsMap\Arterialspacetimediagram_verdor;

class Arterialspacetimediagram_model extends CI_Model
{
    private $tb = '';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function getSpaceTimeDiagram($data)
    {
    	$vals = [];
    	$this->Arterialspacetimediagram_verdor->getSpaceTimeDiagram($vals);
    }
}