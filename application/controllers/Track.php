<?php
/***************************************************************
# 路口类
# user:ningxiangbing@didichuxing.com
# date:2018-03-02
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Didi\Cloud\ItsMap\Track_vendor;

class Track extends MY_Controller {
	public function __construct(){
		parent::__construct();
	}

	/**
	* 获取散点图
	*/
	public function getScatterPlot() {
		$vals = [
            'junctionId' => '2017030116_4875814',
            'flowId'     => '2017030116_i_490122360_2017030116_o_64019800',
            'rtimeVec'   => [
                [
                    'mapVersion' => 'c25101a793840cc6abf3819813823d82',
                    'startTS'    => '1522252800',
                    'endTS'      => '1522339200'
                ]
            ],
            'x'   => -100,
            'y'   => 100,
            'num' => 10
        ];
		$track_mtraj = new Track_vendor();
		$res = $track_mtraj->getScatterMtraj($vals);
		$res = (array)$res;
		$res = (array)$res['scatterPoints'];
		echo "<pre>";print_r($res);
		exit;
	}

	/**
	* 获取时空图
	*/
	public function getSpaceTimeMtraj() {
		$params = $this->input->post();
		$vals = [
            'junctionId' => '2017030116_4875814',
            'flowId'     => '2017030116_i_490122360_2017030116_o_64019800',
            'rtimeVec'   => [
                [
                    'mapVersion' => 'c25101a793840cc6abf3819813823d82',
                    'startTS'    => '1522252800',
                    'endTS'      => '1522339200'
                ]
            ],
            'x'   => -100,
            'y'   => 100,
            'num' => 10
        ];

		$track_mtraj = new Track_vendor();
		$res = $track_mtraj->getSpaceTimeMtraj($vals);
		$res = (array)$res;
		//$res = (array)$res['scatterPoints'];
		echo "<pre>";print_r($res);
		exit;
	}
}