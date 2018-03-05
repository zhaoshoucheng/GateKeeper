<?php
/**********************************************************
* 简易参数验证器

   -------------------------------------------------------
  |暂支持:													  |
  |	nullunable   :校验字符串、数组不能为空					  |
  |	min:value    :校验数字类型大于最小值、字符串长度大于最小值	  |
  |	max:value    :校验数字类型小于最大值、字符串长度小于最大值	  |
  |	after:value  :校验日期（时间戳）在给定日期（时间戳）之后	  |
  |	before:value :校验日期（时间戳）在给定日期（时间戳）之前	  |
   -------------------------------------------------------

* user:ningxiangbing@didichuxing.com
* date:2018-01-30
**********************************************************/

class Validate {
	private static $cfg_message = [
		'nullunable'	=> 'The :paramname cannot be empty.',
		'min'			=> [
								'numeric'	=> 'The :paramname must be greater than :min.',
								'string'	=> 'The character length of :paramname must be greater than :min.'
							],
		'max'			=> [
								'numeric'	=> 'The :paramname must be less than :max.',
								'string'	=> 'The character length of :paramname must be less than :max.'
							],
		'before'		=> 'The :paramname must be before :before.',
		'after'			=> 'The :paramname must be after :after.',
	];

	private static $custom_msg = '';

	/**
	* 校验参数
	* @param data 			校验数组
	* @param rules 			规则数组
	* @param custom_msg 	自定义错误信息
	* @return ['status'=>true/false, 'errmsg'=>'']
	*/
	public static function make(&$data, $rules, $custom_msg = ''){
		if(empty($data)){
			return ['status'=>false, 'errmsg'=> 'An empty data set.'];
		}
		if(count($rules) < 1){
			return ['status'=>false, 'errmsg'=> 'The rules cannot be empty.'];
		}

		self::$custom_msg = $custom_msg;

		// 循环规则
		foreach($rules as $k=>$v){
			$temprules = explode('|', $v);
			$stayValue = array_key_exists($k, $data) ? $data[$k] : '';
			foreach($temprules as $kk=>$vv){
				switch ($vv) {
					// nullunable 不为空
					case 'nullunable':
						$res = self::nullunable($k, $stayValue);
						if(!$res['status']){
							return ['status'=>false, 'errmsg'=>$res['errmsg']];
						}
						break;
					// min:value 最小value
					case strstr($vv, 'min:') !== false:
						$res = self::min($k, $stayValue, $vv);
						if(!$res['status']){
							return ['status'=>false, 'errmsg'=>$res['errmsg']];
						}
						break;
					// max:vallue 最大value
					case strstr($vv, 'max:') !== false:
						$res = self::max($k, $stayValue, $vv);
						if(!$res['status']){
							return ['status'=>false, 'errmsg'=>$res['errmsg']];
						}
						break;
					// after:date 在给定的日期之后
					case strstr($vv, 'after:') !== false:
						$res = self::after($k, $stayValue, $vv);
						if(!$res['status']){
							return ['status'=>false, 'errmsg'=>$res['errmsg']];
						}
						break;

					// after:date 在给定的日期之后
					case strstr($vv, 'before:') !== false:
						$res = self::before($k, $stayValue, $vv);
						if(!$res['status']){
							return ['status'=>false, 'errmsg'=>$res['errmsg']];
						}
						break;
					default:
						$errmsg = 'Unknown rules.';
						return ['status'=>false, 'errmsg'=>$errmsg];
						break;
				}
			}
		}

		return ['status'=> true];
	}

	/**
	* 判断不为空
	* @param param 	参数名
	* @param value 	参数值
	* @return ['status'=>true/false, 'errmsg'=>'']
	*/
	private static function nullunable($param, $value){
		if(is_object($value)){
			return ['status'=>false, 'errmsg'=>'The ' . $param . ' is a object.'];
		}else if(is_array($value)){
			if(count($value) < 1){
				return ['status'=>false, 'errmsg'=>self::combinErrmsg('nullunable', $param)];
			}
		}

		if(empty(strval($value))){
			return ['status'=>false, 'errmsg'=>self::combinErrmsg('nullunable', $param)];
		}

		return ['status'=>true];
	}

	/**
	* max:value 验证字段必须小于最大值
	* @param param 	参数名
	* @param value 	参数值
	* @param rule 	max:value
	* @return ['status'=>true/false, 'errmsg'=>'']
	*/
	private static function max($param, $value, $rule){
		$rule_arr = explode(':', $rule);

		if(is_numeric($value)){
			if($value > (int)$rule_arr[1]){
				return ['status'=>false, 'errmsg'=>self::combinErrmsg('max', $param, 'numeric', $rule_arr[1])];
			}
		}else if(is_string($value)){
			if(mb_strlen($value) > (int)$rule_arr[1]){
				return ['status'=>false, 'errmsg'=>self::combinErrmsg('max', $param, 'string', $rule_arr[1])];
			}
		}else{
			return ['status'=>false, 'errmsg'=>'Other types of validation are not supported for the time being.'];
		}

		return ['status'=>true];
	}

	/**
	* min:value 验证字段必须大于最小值
	* @param param 	参数名
	* @param value 	参数值
	* @param rule 	min:value
	* @return ['status'=>true/false, 'errmsg'=>'']
	*/
	private static function min($param, $value, $rule){
		$rule_arr = explode(':', $rule);

		if(is_numeric($value)){
			if($value < (int)$rule_arr[1]){
				return ['status'=>false, 'errmsg'=>self::combinErrmsg('min', $param, 'numeric', $rule_arr[1])];
			}
		}else if(is_string($value)){
			if(mb_strlen($value) < (int)$rule_arr[1]){
				return ['status'=>false, 'errmsg'=>self::combinErrmsg('min', $param, 'string', $rule_arr[1])];
			}
		}else{
			return ['status'=>false, 'errmsg'=>'Other types of validation are not supported for the time being.'];
		}

		return ['status'=>true];
	}

	/**
	* after:value 验证字段在给定日期之后
	* @param param 	参数名
	* @param value 	参数值
	* @param rule 	after:value
	* @return ['status'=>true/false, 'errmsg'=>'']
	*/
	private static function after($param, $value, $rule){
		$rule_arr = explode(':', $rule);

		if((int)$value - (int)$rule_arr[1] <= 0){
			return ['status'=>false, 'errmsg'=>self::combinErrmsg('after', $param, '', date('Y-m-d', $rule_arr[1]))];
		}

		return ['status'=>true];
	}

	/**
	* before:value 验证字段在给定日期之前
	* @param param 	参数名
	* @param value 	参数值
	* @param rule 	before:value
	* @return ['status'=>true/false, 'errmsg'=>'']
	*/
	private static function before($param, $value, $rule){
		$rule_arr = explode(':', $rule);
		if((int)$value >= $rule_arr[1]){
			return ['status'=>false, 'errmsg'=>self::combinErrmsg('before', $param, '', date('Y-m-d', $rule_arr[1]))];
		}

		return ['status'=>true];
	}

	/**
	* 组合错误信息
	* @param rule 		验证规则名称
	* @param param 		参数名
	* @param subrule	二级验证规则
	* @param value 		规则值
	* @return string
	*/
	private static function combinErrmsg($rule, $param, $subrule = '', $value = ''){
		$errmsg = '';

		if(!empty(self::$custom_msg[$param])){
			$errmsg = self::$custom_msg[$param];
		}else{
			if(empty($subrule)){
				$errmsg = str_replace(':paramname', $param, self::$cfg_message[$rule]);
			}else{
				$errmsg = str_replace(':paramname', $param, self::$cfg_message[$rule][$subrule]);
			}

			if(!empty($value) || $value >= 0){
				$errmsg = str_replace(':' . $rule, $value, $errmsg);
			}
		}

		return $errmsg;
	}
}