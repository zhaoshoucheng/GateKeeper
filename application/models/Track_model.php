<?php

class Track_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->model('junction_model');
		$this->load->model('taskdateversion_model');
		$this->load->model('timing_model');
	}

	/**
	* 获取时空、散点图轨迹数据
	* @param $data['task_id']     interger 任务ID
	* @param $data['junction_id'] string   城市ID
	* @param $data['flow_id']     string   相位ID （flow_id）
	* @param $data['search_type'] interger 搜索类型 查询类型 1：按方案查询 0：按时间点查询
	* @param $data['time_point']  string   时间点 当search_type = 0 时 必传 格式：00:00
	* @param $data['time_range']  string   时间段 当search_type = 1 时 必传 格式：00:00-00:30
	* @param $type                string   获取轨迹类型
	* @return json
	*/
	public function getTrackData($data, $type) {
		if(empty($data) || empty($type)){
			return [];
		}

		// 获取路口详情 dates start_time end_time movements
		$junction_info = $this->junction_model->getJunctionInfoForTheTrack($data);
		if(!$junction_info){
			return [];
		}

		// 获取 mapversion
		$mapversions = $this->taskdateversion_model->select($junction_info['task_id'], $junction_info['dates']);
		if(!$mapversions){
			return [];
		}

		// 获取 配时信息 周期 相位差 绿灯开始结束时间
		$timing_data = [
			'junction_id' => $junction_info['junction_id'],
			'dates'       => explode(',', $junction_info['dates']),
			'time_range'  => $junction_info['start_time'] . '-' . date("H:i", strtotime($junction_info['end_time']) - 60),
			'flow_id'	  => trim($params['flow_id'])
		];
		$timing = $this->timing_model->getFlowTimingInfoForTheTrack($timing_data);
		if(!$timing){
			return [];
		}

		// 组织thrift所需rtimeVec数组
		foreach($mapversions as $k=>$v){
			$rtimeVec[$k]['mapVersion'] = $v['map_version_md5'];
			$rtimeVec[$k]['startTS'] = strtotime($v['date'] . ' ' . $junction_info['start_time']);
			$rtimeVec[$k]['endTS'] = strtotime($v['date'] . ' ' . $junction_info['end_time']);
		}

		// 组织thrift所需filterData数组
		$af_condition = [];
		$bf_condition = [];
		$num = [];
		if(!empty($junction_info['af_condition'])){
			$af_condition = explode(',', trim($junction_info['af_condition']));
		}
		if(!empty($junction_info['bf_condition'])){
			$bf_condition = explode(',', trim($junction_info['bf_condition']));
		}
		if(!empty($junction_info['num'])){
			$num = explode(',', trim($junction_info['num']));
		}

		$sample_data = [];
		if(!empty($bf_condition) && !empty($af_condition) && !empty($num)){
			foreach($bf_condition as $k=>$v){
				// X
				if(trim($v) === 'no_stop'){
					$sample_data[$k]['xType'] = 2;
					$sample_data[$k]['xData']['noStop'] = 1;
				}else if(strstr($v, '|') !== false && strstr($v, '#') === false){
					$temp_arr = explode("|", $v);
					$sample_data[$k]['xType'] = 3;
					$sample_data[$k]['xData']['lR'] = $temp_arr[0];
					$sample_data[$k]['xData']['rR'] = $temp_arr[1];
				}else if(strstr($v, '|') !== false && strstr($v, '#') !== false){
					$temp_arr = explode('#', $v);
					$temp_val = explode('|', $temp_arr[1]);
					$sample_data[$k]['xType'] = 4;
					$sample_data[$k]['xData']['noStop'] = 1;
					$sample_data[$k]['xData']['lR'] = $temp_val[0];
					$sample_data[$k]['xData']['rR'] = $temp_val[1];
				}else{
					$sample_data[$k]['xType'] = 1;
					$sample_data[$k]['xData']['all'] = true;
				}
				// Y
				if(trim($af_condition[$k]) === 'no_stop'){
					$sample_data[$k]['yType'] = 2;
					$sample_data[$k]['yData']['noStop'] = -1;
				}else if(strstr($af_condition[$k], '|') !== false && strstr($af_condition[$k], '#') === false){
					$temp_arr = explode("|", $af_condition[$k]);
					$sample_data[$k]['yType'] = 3;
					$sample_data[$k]['yData']['lR'] = $temp_arr[0];
					$sample_data[$k]['yData']['rR'] = $temp_arr[1];
				}else if(strstr($af_condition[$k], '|') !== false && strstr($af_condition[$k], '#') !== false){
					$temp_arr = explode('#', $af_condition[$k]);
					$temp_val = explode('|', $temp_arr[1]);
					$sample_data[$k]['yType'] = 4;
					$sample_data[$k]['yData']['noStop'] = -1;
					$sample_data[$k]['yData']['lR'] = $temp_val[0];
					$sample_data[$k]['yData']['rR'] = $temp_val[1];
				}else{
					$sample_data[$k]['yType'] = 1;
					$sample_data[$k]['yData']['all'] = true;
				}
				$sample_data[$k]['num'] = $num[$k];
			}
		}
		echo "<pre> sample_data = ";print_r($sample_data);
		echo "<hr><pre>junction_info = ";print_r($junction_info);


		$vals = [
            'junctionId' => trim($junction_info['junction_id']),
            'flowId'     => trim($params['flow_id']),
            'rtimeVec'   => $rtimeVec,
            /*'junctionId' => '2017030116_4875814',
            'flowId'     => '2017030116_i_490122360_2017030116_o_64019800',
            'rtimeVec'   => [
                [
                    'mapVersion' => 'c25101a793840cc6abf3819813823d82',
                    'startTS'    => '1522252800',
                    'endTS'      => '1522339200'
                ]
            ],*/
            'filterData' => $sample_data
        ];

        // 新的相位差 用任务结果中的clock_shift + 配时的相位差
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
					$result[$k]['base']['map_second'] = ($result[$k]['list'][$kk]['second'] - $new_offset) % $timing['cycle'] + $new_offset;
				}

				$result[$k]['list'][$kk]['value'] = $vv['stopLineDistance'];
			}
		}
		echo "<hr><pre>junction_info = ";print_r($junction_info);
		echo "<hr>mapVersion = ";print_r($mapversions);
		echo "<hr>timing = ";print_r($timing);
		echo "<hr><pre>result = ";print_r($result);
		foreach($result as $k=>$v){
			foreach($v['list'] as $kk=>$vv){
				// 时间
				$result_data['dataList'][$k][$kk][0] = $vv['second'] - ($v['base']['second'] - $v['base']['map_second']);
				// 值
				$result_data['dataList'][$k][$kk][1] = round($vv['value'], 5) * -1;
			}
		}
		$result_data['signal_range'] = [];
		if($timing['state'] == 1){ // 绿灯
			// 绿灯开始时间
			$green_signal_start = $cycle_start_time + $timing['start_time'];
			// 绿灯结束时间
			$green_signal_end = $green_signal_start + $timing['duration'];

			if($green_signal_start > $cycle_start_time){
				$result_data['signal_range'][$cycle_start_time]['type'] = 0;
				$result_data['signal_range'][$cycle_start_time]['from'] = $cycle_start_time;
				$result_data['signal_range'][$cycle_start_time]['to'] = $green_signal_start;
			}

			$result_data['signal_range'][$green_signal_start]['type'] = 1;
			$result_data['signal_range'][$green_signal_start]['from'] = $green_signal_start;
			$result_data['signal_range'][$green_signal_start]['to'] = $green_signal_end;

			if($cycle_end_time > $green_signal_end){
				$result_data['signal_range'][$green_signal_end]['type'] = 0;
				$result_data['signal_range'][$green_signal_end]['from'] = $green_signal_end;
				$result_data['signal_range'][$green_signal_end]['to'] = $cycle_end_time;
			}
		}
		if(!empty($result_data['signal_range'])){
			$result_data['signal_range'] = array_values($result_data['signal_range']);
		}
		$result_data['info']['id'] = trim($params['flow_id']);
		$result_data['info']['comment'] = $timing['comment'];

		echo "<hr><pre>result = ";print_r($result_data);exit;

		return $result_data;
	}
}
