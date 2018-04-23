<?php
/********************************************
# desc:    轨迹数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-04-13
********************************************/

use Didi\Cloud\ItsMap\Track_vendor;

class Track_model extends CI_Model {

	private $email_to = 'ningxiangbing@didichuxing.com';
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
			$content = "form_data = " . json_encode($data);
			sendMail($this->email_to, '获取时空/散点图（'.$type.'）->获取路口详情为空', $content);
			return [];
		}

		// 获取 mapversion
		$mapversions = $this->taskdateversion_model->select($junction_info['task_id'], explode(',', $junction_info['dates']));
		if(!$mapversions){
			$content = "form_data = " . json_encode(['task_id'=>$junction_info['task_id'], 'dates'=>$junction_info['dates']]);
			sendMail($this->email_to, '获取时空/散点图（'.$type.'）->获取mapversion为空', $content);
			return [];
		}

		// 获取 配时信息 周期 相位差 绿灯开始结束时间
		$timing_data = [
			'junction_id' => $junction_info['junction_id'],
			'dates'       => explode(',', $junction_info['dates']),
			'time_range'  => $junction_info['start_time'] . '-' . date("H:i", strtotime($junction_info['end_time']) - 60),
			'flow_id'	  => trim($data['flow_id'])
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
				}else if(strpos($v, '|') !== false && strpos($v, '#') === false){
					$temp_arr = explode("|", $v);
					$sample_data[$k]['xType'] = 3;
					$sample_data[$k]['xData']['lR'] = $temp_arr[0];
					$sample_data[$k]['xData']['rR'] = $temp_arr[1];
				}else if(strpos($v, '|') !== false && strpos($v, '#') !== false){
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
				}else if(strpos($af_condition[$k], '|') !== false && strpos($af_condition[$k], '#') === false){
					$temp_arr = explode("|", $af_condition[$k]);
					$sample_data[$k]['yType'] = 3;
					$sample_data[$k]['yData']['lR'] = $temp_arr[0];
					$sample_data[$k]['yData']['rR'] = $temp_arr[1];
				}else if(strpos($af_condition[$k], '|') !== false && strpos($af_condition[$k], '#') !== false){
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

		$vals = [
            'junctionId' => trim($junction_info['junction_id']),
            'flowId'     => trim($data['flow_id']),
            'rtimeVec'   => $rtimeVec,
            'filterData' => $sample_data
        ];

        $result_data = $this->$type($vals, $timing, $junction_info);

		return $result_data;
	}

	/**
	* 获取时空图轨迹数据
	*/
	private function getSpaceTimeMtraj($vals, $timing, $junction_info) {
		// 新的相位差 用任务结果中的clock_shift + 配时的相位差
        $new_offset = ($timing['offset'] + $junction_info['clock_shift']) % $timing['cycle'];
        $cycle_start_time = $new_offset;
        $cycle_end_time = $timing['cycle'] + $new_offset;
		$track_mtraj = new Track_vendor();
		$res = $track_mtraj->getSpaceTimeMtraj($vals);

		$res = (array)$res;
		if($res['errno'] != 0){
			return [];
		}
		$result = [];
		if(!empty($res['matchPoints'])){
			foreach($res['matchPoints'] as $k=>$v){
				$tem_result[$k]['base']['time'] = 0;
				foreach($v as $kk=>&$vv){
					$vv = (array)$vv;
					$temp_time = date_parse(date("H:i:s", $vv['timestamp']));
					$temp_second = $temp_time['hour'] * 3600 + $temp_time['minute'] * 60 + $temp_time['second'];
					$tem_result[$k]['list'][$temp_second]['second'] = $temp_second;
					// 找到第一个大于的通过路口距离，以此为标准映射到周期内
					if($vv['stopLineDistance'] > 0 && $tem_result[$k]['base']['time'] == 0){
						$tem_result[$k]['base']['time'] = $vv['timestamp'];
						$tem_result[$k]['base']['second'] = $temp_second;
						$tem_result[$k]['base']['map_second'] = ($tem_result[$k]['list'][$temp_second]['second'] - $new_offset) % $timing['cycle'] + $new_offset;
					}

					$tem_result[$k]['list'][$temp_second]['value'] = $vv['stopLineDistance'];
				}
				ksort($tem_result[$k]['list']);
				$first = current($tem_result[$k]['list']);
				$result[$first['second']]['base'] = $tem_result[$k]['base'];
				$result[$first['second']]['list'] = array_values($tem_result[$k]['list']);
			}
		}
		if(!empty($result)){
			ksort($result);
			$result = array_values($result);

			foreach($result as $k=>$v){
				foreach($v['list'] as $kk=>$vv){
					// 时间
					$result_data['dataList'][$k][$kk][0] = $vv['second'] - ($v['base']['second'] - $v['base']['map_second']);
					// 值
					$result_data['dataList'][$k][$kk][1] = round($vv['value'], 5) * -1;
				}
			}
		}
		// 组织信号灯区间
		$result_data['signal_range'] = [];
		$bf_green_end = $cycle_start_time;
		// 剩余时间 默认整个周期
		$surplus_time = $cycle_end_time;

		foreach($timing['signal'] as $k=>$v){
			if($v['state'] == 1){ // 绿灯
				$green_start = $v['start_time'] + $cycle_start_time;
				// 当绿灯开始时间 == 周期开始时间
				if($green_start == $cycle_start_time){
					// 信号灯状态 0 红灯 1绿灯
					$result_data['signal_range'][$green_start]['type'] = 1;
					// 本次绿灯开始时间
					$result_data['signal_range'][$green_start]['from'] = $green_start;
					// 本次绿灯结束时间
					$result_data['signal_range'][$green_start]['to'] = $green_start + $v['duration'];
				// 与上次绿灯结束时间比较 如果大于且小于周期结束时间，则标记红灯 PS:$timing['signal']已按时间正序排列
				}else if($green_start > $bf_green_end && $green_start < $cycle_end_time){
					// 信号灯状态 0 红灯 1绿灯
					$result_data['signal_range'][$bf_green_end]['type'] = 0;
					// 红灯开始时间 上次绿灯结束时间
					$result_data['signal_range'][$bf_green_end]['from'] = $bf_green_end;
					// 红灯结束时间 本次绿灯开始时间
					$result_data['signal_range'][$bf_green_end]['to'] = $green_start;

					// 信号灯状态 0 红灯 1绿灯
					$result_data['signal_range'][$green_start]['type'] = 1;
					// 本次绿灯开始时间
					$result_data['signal_range'][$green_start]['from'] = $green_start;
					// 本次绿灯结束时间
					$result_data['signal_range'][$green_start]['to'] = $green_start + $v['duration'];
				}
				// 更新上一次绿灯结束时间
				$bf_green_end = $green_start + $v['duration'];

				// 更新剩余时间
				$surplus_time = $cycle_end_time - ($green_start + $v['duration']);
			}
		}
		if($surplus_time > 0){
			// 信号灯状态 0 红灯 1绿灯
			$result_data['signal_range'][$bf_green_end]['type'] = 0;
			// 红灯开始时间 上次绿灯结束时间
			$result_data['signal_range'][$bf_green_end]['from'] = $bf_green_end;
			// 红灯结束时间 本次绿灯开始时间
			$result_data['signal_range'][$bf_green_end]['to'] = $bf_green_end + $surplus_time;
		}

		if(!empty($result_data['signal_range'])){
			$result_data['signal_range'] = array_values($result_data['signal_range']);
		}
		$result_data['info']['id'] = trim($junction_info['flow_id']);
		$result_data['info']['comment'] = $timing['comment'];

		return $result_data;
	}

	/**
	* 获取散点图轨迹数据
	*/
	private function getScatterMtraj($vals, $timing, $junction_info) {
		$track_mtraj = new Track_vendor();
		$res = $track_mtraj->getScatterMtraj($vals);
		$res = (array)$res;
		if($res['errno'] != 0){
			return [];
		}
		if(!empty($res['scatterPoints'])){
			foreach($res['scatterPoints'] as $k=>&$v){
				$v = (array)$v;
				$time = $v['stopLineTimestamp'];
				$temp_time = date("H:i:s", $time);
				// 时间
				$result_data['dataList'][$time][0] = $temp_time;
				// 值
				$result_data['dataList'][$time][1] = round($v['stopDelayBefore']);
			}
		}

		if(!empty($result_data['dataList'])){
			ksort($result_data['dataList']);
			$result_data['dataList'] = array_values($result_data['dataList']);
		}

		// 绿灯时长
		$green_time = 0;
		foreach($timing['signal'] as $k=>$v){
			if($v['state'] == 1){ // 绿灯
				$green_time += $v['duration'];
			}
		}
		$result_data['signal_detail']['cycle'] = (int)$timing['cycle'];
		$result_data['signal_detail']['red_duration'] = (int)$timing['cycle'] - $green_time;
		$result_data['signal_detail']['green_duration'] = $green_time;

		$result_data['info']['id'] = trim($junction_info['flow_id']);
		$result_data['info']['comment'] = $timing['comment'];
		return $result_data;
	}
}
