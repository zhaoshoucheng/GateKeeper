<?php

/**
 * 
 */
class Validator
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

    public function create($data, $rules)
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

		return $this;
	}

	public static function make($data, $rules)
    {
        return (new static)->create($data, $rules);
    }

    public function fail()
    {
        return !empty($this->messages);
    }

    public function messages()
    {
        return $this->messages;
    }

    public function firstError()
    {
    	return $this->messages[0] ?? 'Error';
    }

    private function required($key)
    {
        if(!isset($this->data[$key]))
            $this->messages[] = "$key 不存在";
    }

    private function min($key, $value)
    {
        if(!isset($this->data[$key]))
            return;
        if(is_numeric($this->data[$key]) && $this->data[$key] < $value)
            $this->messages[] = "$key 的值小于 $value";
        elseif (is_string($this->data[$key]) && strlen($this->data[$key]) < $value)
            $this->messages[] = "$key 的长度小于 $value";
    }

    private function max($key, $value)
    {
        if(!isset($this->data[$key]))
            return;
        if(is_numeric($this->data[$key]) && $this->data[$key] > $value)
            $this->messages[] = "$key 的值小于 $value";
        elseif (is_string($this->data[$key]) && strlen($this->data[$key]) > $value)
            $this->messages[] = "$key 的长度小于 $value";
    }

    private function numeric($key)
    {
        if(!isset($this->data[$key]))
            return;
        if(!is_numeric($this->data[$key]))
            $this->messages[] = "$key 的值不是数值类型";
    }

    private function int($key)
    {
        if(!isset($this->data[$key]))
            return;
        if(!is_int($this->data[$key]))
            $this->messages[] = "$key 的值不是整数类型";
    }

    private function float($key)
    {
        if(!isset($this->data[$key]))
            return;
        if(!is_float($this->data[$key]))
            $this->messages[] = "$key 的值不是小数类型";
    }

    private function in($key, ...$ins)
    {
        if(!isset($this->data[$key]))
            return;
        if(!in_array($this->data[$key], $ins))
            $this->messages[] = "$key 的值不在给定集合中";
    }

    private function notIn($key, ...$ins)
    {
        if(!isset($this->data[$key]))
            return;
        if(in_array($this->data[$key], $ins))
            $this->messages[] = "$key 的值在给定集合中";
    }

    private function between($key, $start, $end)
    {
        if(!isset($this->data[$key]))
            return;
        if(is_numeric($this->data[$key]) && ($this->data[$key] < $start || $this->data[$key] > $end))
            $this->messages[] = "$key 的值在给定集合中";
    }

    private function date($key, $format)
    {
        if(!isset($this->data[$key]))
            return;
        if(date($format, strtotime($this->data[$key])) !== $this->data[$key])
            $this->messages[] = "$key 的格式错误";
    }
}