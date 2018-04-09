<?php
/********************************************
# desc:    路网数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-04-08
********************************************/

class Waymap_model extends CI_Model {

	public function __construct(){
		parent::__construct();

		$this->load->config('nconf');
	}

	/**
	* 根据路口ID串获取路口名称
	* @param logic_junction_ids 	逻辑路口ID串 	string
	* @return array
	*/
	public function getJunctionInfo($ids){
		$this->load->helper('http');
		$data['logic_ids'] = $ids;
		$data['token'] = $this->config->item('waymap_token');

		try {
			$res = httpGET($this->config->item('waymap_interface') . '/flow-duration/map/many', $data);
			if(!$res){
				// 日志
				return [];
			}
			$res = json_decode($res, true);
			if($res['errorCode'] != 0 || !isset($res['data']) || empty($res['data'])){
				return [];
			}
			return $res['data'];
		} catch (Exception $e) {
			return [];
		}
	}

	/**
	* 获取全城路口
	* @param city_id 		Y 城市ID
	* @return array
	*/
	public function getAllCityJunctions($city_id){
		if((int)$city_id < 1){
			return false;
		}

		/*---------------------------------------------------
		| 先去redis中获取，如没有再调用api获取且将结果放入redis中 |
		-----------------------------------------------------*/
		$this->load->model('redis_model');
		$redis_key = "all_city_junctions_{$city_id}";

		// 获取redis中数据
		$city_junctions = $this->redis_model->getData($redis_key);
		if(!$city_junctions){
			$this->load->helper('http');

			$data = [
						'city_id'	=> $city_id,
						'token'		=> $this->config->item('waymap_token'),
						'offset'	=> 0,
						'count'		=> 10000
					];
			try {
				$res = httpGET($this->config->item('waymap_interface') . '/flow-duration/map/getList', $data);
				if(!$res){
					// 添加日志、发送邮件
					return false;
				}
				$res = json_decode($res, true);
				if(isset($res['errorCode']) && $res['errorCode'] == 0 && isset($res['data']) && count($res['data']) >= 1){
					$this->redis_model->deleteData($redis_key);
					$this->redis_model->setData($redis_key, json_encode($res['data']));
					$this->redis_model->setExpire($redis_key, 3600 * 24);
					$city_junctions = $res['data'];
				}
			} catch (Exception $e) {
				return false;
			}
		}else{
			$city_junctions = json_decode($city_junctions, true);
		}

		return $city_junctions;
	}
}