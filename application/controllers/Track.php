<?php
/***************************************************************
# 轨迹类
# user:ningxiangbing@didichuxing.com
# date:2018-04-13
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Didi\Cloud\ItsMap\Track_vendor;

class Track extends MY_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->model('junction_model');
		$this->load->model('taskdateversion_model');
		$this->load->model('timing_model');
	}

	/**
	* 获取散点图
	* @param task_id     interger 任务ID
	* @param junction_id string   城市ID
	* @param flow_id     string   相位ID （flow_id）
	* @param search_type interger 搜索类型 查询类型 1：按方案查询 0：按时间点查询
	* @param time_point  string   时间点 当search_type = 0 时 必传 格式：00:00
	* @param time_range  string   时间段 当search_type = 1 时 必传 格式：00:00-00:30
	* @return json
	*/
	public function getScatterPlot() {
		$params = $this->input->post();
		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'     => 'min:1',
				'junction_id' => 'nullunable',
				'search_type' => 'min:0',
				'flow_id'     => 'nullunable'
			]
		);
		if(!$validate['status']){
			$this->errno = ERR_PARAMETERS;
			$this->errmsg = $validate['errmsg'];
			return;
		}

		if((int)$params['search_type'] == 0){
			if(empty($params['time_point'])){
				$this->errno = ERR_PARAMETERS;
				$this->errmsg = 'The time_point cannot be empty.';
				return;
			}
		}else{
			if(empty($params['time_range'])){
				$this->errno = ERR_PARAMETERS;
				$this->errmsg = 'The time_range cannot be empty.';
				return;
			}
			$time_range = array_filter(explode('-', $params['time_range']));
			if(empty($time_range[0]) || empty($time_range[1])){
				$this->errno = ERR_PARAMETERS;
				$this->errmsg = 'The time_range is wrong.';
				return;
			}
		}

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
	* @param task_id     interger 任务ID
	* @param junction_id string   城市ID
	* @param flow_id     string   相位ID （flow_id）
	* @param search_type interger 搜索类型 查询类型 1：按方案查询 0：按时间点查询
	* @param time_point  string   时间点 当search_type = 0 时 必传 格式：00:00
	* @param time_range  string   时间段 当search_type = 1 时 必传 格式：00:00-00:30
	* @return json
	*/
	public function getSpaceTimeMtraj() {
		$params = $this->input->post();
		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'     => 'min:1',
				'junction_id' => 'nullunable',
				'search_type' => 'min:0',
				'flow_id'     => 'nullunable'
			]
		);
		if(!$validate['status']){
			$this->errno = ERR_PARAMETERS;
			$this->errmsg = $validate['errmsg'];
			return;
		}

		if((int)$params['search_type'] == 0){
			if(empty($params['time_point'])){
				$this->errno = ERR_PARAMETERS;
				$this->errmsg = 'The time_point cannot be empty.';
				return;
			}
		}else{
			if(empty($params['time_range'])){
				$this->errno = ERR_PARAMETERS;
				$this->errmsg = 'The time_range cannot be empty.';
				return;
			}
			$time_range = array_filter(explode('-', $params['time_range']));
			if(empty($time_range[0]) || empty($time_range[1])){
				$this->errno = ERR_PARAMETERS;
				$this->errmsg = 'The time_range is wrong.';
				return;
			}
		}

		// 获取路口详情 dates start_time end_time movements
		$junction_info = $this->junction_model->getJunctionInfoForTheTrack($params);
		if(!$junction_info){
			return;
		}

		// 获取 mapversion
		$mapversions = $this->taskdateversion_model->select($junction_info['task_id'], $junction_info['dates']);
		if(!$mapversions){
			return;
		}

		// 获取 配时信息 周期 相位差 绿灯开始结束时间
		$timing_data = [
			'junction_id' => $junction_info['junction_id'],
			'dates'       => explode(',', $junction_info['dates']),
			'time_range'  => $junction_info['start_time'] . '-' . date("H:i", strtotime($junction_info['end_time']) - 60),
			'flow_id'	  => trim($params['flow_id'])
		];
		$timing = $this->timing_model->getFlowTimingInfoForTheTrack($timing_data);

		foreach($mapversions as $k=>$v){
			$rtimeVec[$k]['mapVersion'] = $v['map_version_md5'];
			$rtimeVec[$k]['startTS'] = strtotime($v['date'] . ' ' . $junction_info['start_time']);
			$rtimeVec[$k]['endTS'] = strtotime($v['date'] . ' ' . $junction_info['end_time']);
		}

		$vals = [
            /*'junctionId' => trim($junction_info['junction_id']),
            'flowId'     => trim($params['flow_id']),
            'rtimeVec'   => $rtimeVec,*/
            'junctionId' => '2017030116_4875814',
            'flowId'     => '2017030116_i_490122360_2017030116_o_64019800',
            'rtimeVec'   => [
                [
                    'mapVersion' => 'c25101a793840cc6abf3819813823d82',
                    'startTS'    => '1522252800',
                    'endTS'      => '1522339200'
                ]
            ],
            'x'   => -50,
            'y'   => 50,
            'num' => 10
        ];

        // 新的相位差
        $new_offset = ($timing['offset'] + $junction_info['clock_shift']) % $timing['cycle'];
        $cycle_start_time = $new_offset;
        $cycle_end_time = $timing['cycle'] + $new_offset;

		$track_mtraj = new Track_vendor();
		$res = $track_mtraj->getSpaceTimeMtraj($vals);
		$res = (array)$res;
		for($i=0; $i < 100; $i++){
			$temp_res['matchPoints'][$i] = $res['matchPoints'][$i];
		}
		foreach($temp_res['matchPoints'] as $k=>$v){
			$result[$k]['base']['time'] = 0;
			foreach($v as $kk=>&$vv){
				$vv = (array)$vv;
				$temp_time = date_parse(date("H:i:s", $vv['timestamp']));
				$temp_second = $temp_time['hour'] * 3600 + $temp_time['minute'] * 60 + $temp_time['second'];
				$result[$k]['list'][$kk]['second'] = $temp_second;
				// 找到第一个大于的通过路口距离，以此为标准映射到周期内
				if($vv['stopLineDistance'] > 0 && $result[$k]['base']['time'] == 0){
					$result[$k]['base']['time'] = $vv['timestamp'];
					$result[$k]['base']['second'] = $result[$k]['list'][$kk]['second'];
					$result[$k]['base']['map_second'] = $result[$k]['list'][$kk]['second'] % $timing['cycle'] + $new_offset;
				}

				$result[$k]['list'][$kk]['value'] = $vv['stopLineDistance'];
			}
		}

		foreach($result as $k=>$v){
			foreach($v['list'] as $kk=>$vv){
				$result_data['dataList'][$k][$kk]['value'] = round($vv['value'], 5) * -1;
				$result_data['dataList'][$k][$kk]['time'] = $vv['second'] - ($v['base']['second'] - $v['base']['map_second']);
			}
		}
		$result_data['cycle_range'] = [$cycle_start_time, $cycle_range];
		//echo "<pre>vals = ";print_r($vals);
		echo "<hr><pre>";print_r($result_data);
		echo "<hr><pre>junction_info = ";print_r($junction_info);
		echo "<hr>mapVersion = ";print_r($mapversions);
		echo "<hr>timing = ";print_r($timing);

		return $this->response($result_data);
	}
}