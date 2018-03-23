<?php

namespace Didi\Cloud\ItsMap\Models;

class Maptypeversion extends \Illuminate\Database\Eloquent\Model
{
    protected $table = "maptypeversion";

    public function get($date)
    {
    	// return self::whereIn("date_format(match_date, '%Y-%m-%d')", $date);
    	// return self::selectRaw("date_format(match_date, '%Y-%m-%d') as date, count")->whereRaw("date_format(match_date, '%Y-%m-%d') in (2017-10-10)")->get();
    	// return DB::table('maptypeversion')->get();
    	return $this->whereIn('match_date', $date)->get();
    }

    public function toArray()
    {
        return [
            'match_date' => $this->match_date,
            'maptype' => $this->maptype,
            'count' => $this->count,
            'uniquekey' => $this->uniquekey,
        ];
    }
}