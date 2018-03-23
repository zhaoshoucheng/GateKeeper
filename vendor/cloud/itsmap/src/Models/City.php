<?php

namespace Didi\Cloud\ItsMap\Models;

class City extends \Illuminate\Database\Eloquent\Model
{
    protected $table = "estimate_city";

    public function toArray()
    {
        return [
            'city_id' => $this->city_id,
            'city_name' => $this->city_name,
        ];
    }
}