<?php
/**
 * 周报模块
 */

class Report_model extends CI_Model
{
    public function test()
    {
        return array(
            array(
                'start_time'=>"12:00:00",
                'end_time'=>"13:00:00",
                'logic_junction_id'=>"123456",
                'stop_delay'=>"2.3333",
            ),array(
                'start_time'=>"13:00:00",
                'end_time'=>"14:00:00",
                'logic_junction_id'=>"1234567",
                'stop_delay'=>"1.2222",
            )
        );
    }
}