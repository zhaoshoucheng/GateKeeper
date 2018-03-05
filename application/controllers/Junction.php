<?php
/***************************************************************
# 路口类
# user:ningxiangbing@didichuxing.com
# date:2018-03-02
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Junctiion extends MY_Controller {
	public function __construct(){
		parent::__construct();
	}

	/**
	* 获取全城路口信息
	* @param city_id 		Y 城市ID
	* @param task_id		Y 任务ID
	* @param time_point		Y 评估时间点
	* @param confidence		Y 置信度
	* @param quota_key		Y 指标key
	* @return json
	*/
	public function getAllCityJunctionInfo(){
		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'		=> 'min:1',
				'city_id'		=> 'main:1',
				'time_point'	=> 'nullunable',
				'confidence'	=> 'main:1',
				'quota_key'		=> 'nullunable'
			]
		);
		if(!$validate['status']){
			return $this->response(array(), $errno, $validate['errmsg']);
		}
	}
}