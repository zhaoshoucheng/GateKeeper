<?php

/**
 * 
 */
class Validator2
{
    private $message = [
        'required' => '',
        'min' => '',
        'max' => '',
        'numeric' => '',
        'int' => '',
        'float' => '',
        'in' => '',
        'notIn' => '',
        'equal' => '',
        'between' => '',
        'date' => '',
    ];

    private $data = [];

    private $messages = [];
	
	private function __construct($data, $rules)
	{
		$this->data = $data;

		array_walk($rules, function ($rule, $key) {
            $rules = explode(';', $rule);
            array_walk($rules, function ($rule2) use ($key) {
                @list($method, $params) = explode(':', $rule2);
                $params = explode(',', $params);
                array_unshift($params, $key);
                if(method_exists($this, $method))
                    call_user_func_array([$this, $method], $params);
            });
        });
	}

	public static function make($data, $rules)
    {
        return new static($data, $rules);
    }

    public function fail()
    {
        return empty($this->messages);
    }

    public function messages()
    {
        return $this->messages;
    }

    public function firstError()
    {
    	return $this->messages[0] ?? '';
    }

    private function required($key)
    {
        if(!isset($this->data[$key]))
            $this->messages[] = "$key 不存在";
    }

    private function min($key, $value)
    {
        if(is_numeric($this->data[$key]) && $this->data[$key] < $value)
            $this->messages[] = "$key 的值小于 $value";
        elseif (is_string($this->data[$key]) && strlen($this->data[$key]) < $value)
            $this->messages[] = "$key 的长度小于 $value";
    }

    private function max($key, $value)
    {
        if(is_numeric($this->data[$key]) && $this->data[$key] > $value)
            $this->messages[] = "$key 的值小于 $value";
        elseif (is_string($this->data[$key]) && strlen($this->data[$key]) > $value)
            $this->messages[] = "$key 的长度小于 $value";
    }

    private function numeric($key)
    {
        if(!is_numeric($this->data[$key]))
            $this->messages[] = "$key 的值不是数值类型";
    }

    private function int($key)
    {
        if(!is_int($this->data[$key]))
            $this->messages[] = "$key 的值不是整数类型";
    }

    private function float($key)
    {
        if(!is_float($this->data[$key]))
            $this->messages[] = "$key 的值不是小数类型";
    }

    private function in($key, ...$ins)
    {
        if(!in_array($this->data[$key], $ins))
            $this->messages[] = "$key 的值不在给定集合中";
    }

    private function notIn($key, ...$ins)
    {
        if(in_array($this->data[$key], $ins))
            $this->messages[] = "$key 的值在给定集合中";
    }

    private function between($key, $start, $end)
    {
        if(is_numeric($this->data[$key]) && ($this->data[$key] < $start || $this->data[$key] > $end))
            $this->messages[] = "$key 的值在给定集合中";
    }

    private function date($key, $format)
    {
        if(date($format, strtotime($this->data[$key])) !== $this->data[$key])
            $this->messages[] = "$key 的格式错误";
    }
}