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
		$track_mtraj = new Track_vendor();
		$res = $track_mtraj->getScatterMtraj();
		//$res = (array)$res;
		$res = (array)$res['scatterPoints'];
		echo "<pre>";print_r($res);
		exit;
	}
}