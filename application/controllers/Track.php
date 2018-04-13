<?php
/***************************************************************
# 路口类
# user:ningxiangbing@didichuxing.com
# date:2018-03-02
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Didi\Cloud\ItsMap\Track as Track_mtraj;

class Track extends MY_Controller {
	private $tarck_mtraj;

	public function __construct(){
		parent::__construct();

		$this->tarck_mtraj = new Track_mtraj();
	}

	/**
	* 获取散点图
	*/
	public function getScatterPlot() {
		$res = $this->tarck_mtraj->getScatterMtraj();
		var_dump($res);
	}
}