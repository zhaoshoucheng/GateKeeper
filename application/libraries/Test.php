<?php
//
require __DIR__ . '/Collection.php';
//
//////数据源
//$array = [
//    ['hour' => '10:00', 'flow' => 'aaa', 'value' => 10, 'item' => [ 'name' => 'bbb', 'age' => 10 ]],
//    ['hour' => '10:00', 'flow' => 'bbb', 'value' => 8 , 'item' => [ 'name' => 'aaa', 'age' => 11 ]],
//    ['hour' => '10:30', 'flow' => 'aaa', 'value' => 6 , 'item' => [ 'name' => 'bbb', 'age' => 12 ]],
//    ['hour' => '10:30', 'flow' => 'bbb', 'value' => 15, 'item' => [ 'name' => 'aaa', 'age' => 13 ]],
//    ['hour' => '11:00', 'flow' => 'aaa', 'value' => 13, 'item' => [ 'name' => 'bbb', 'age' => 12 ]],
//    ['hour' => '11:00', 'flow' => 'bbb', 'value' => 5 , 'item' => [ 'name' => 'aaa', 'age' => 13 ]],
//    ['hour' => '11:30', 'flow' => 'aaa', 'value' => 9 , 'item' => [ 'name' => 'bbb', 'age' => 15 ]],
//    ['hour' => '11:30', 'flow' => 'bbb', 'value' => 16, 'item' => [ 'name' => 'aaa', 'age' => 17 ]],
//];
//$array2 = [
//    ['hour' => '10:00', 'flow' => 'aaa', 'value' => 10, 'item' => [ 'name' => 'bbb', 'age' => 10 ]],
//    ['hour' => '10:00', 'flow' => 'bbb', 'value' => 8 , 'item' => [ 'name' => 'aaa', 'age' => 11 ]],
//    ['hour' => '10:30', 'flow' => 'aaa', 'value' => 6 , 'item' => [ 'name' => 'bbb', 'age' => 12 ]],
//    ['hour' => '10:30', 'flow' => 'bbb', 'value' => 15, 'item' => [ 'name' => 'aaa', 'age' => 13 ]],
//    ['hour' => '11:00', 'flow' => 'aaa', 'value' => 13, 'item' => [ 'name' => 'bbb', 'age' => 12 ]],
//    ['hour' => '11:00', 'flow' => 'bbb', 'value' => 5 , 'item' => [ 'name' => 'aaa', 'age' => 13 ]],
//    ['hour' => '11:30', 'flow' => 'aaa', 'value' => 9 , 'item' => [ 'name' => 'bbb', 'age' => 15 ]],
//    ['hour' => '11:30', 'flow' => 'bbb', 'value' => 16, 'item' => [ 'name' => 'aaa', 'age' => 17 ]],
//];
//
////dd(array_merge_recursive($array, $array2));
//$a = 1;
//$b = &$a;
//
//echo (++$b) + (++$b);
////dd(
////
////    Collection::make($array)
////        ->groupBy(['flow', 'item.name'])
////        ->get()
////
////);
//
//
//
////define('STRAIGHT', 1); // 直行
////define('TURN_LEFT', 2); // 左转
////define('TURN_RIGHT', 3); // 右转
////define('TURN_ROUND', 4); // 掉头
////
////define('EAST', 1);
////define('SOUTH', 2);
////define('WEST', 3);
////define('NORTH', 4);
////define('SOUTH_EAST', 5);
////define('SOUTH_WEST', 6);
////define('NORTH_EAST', 7);
////define('NORTH_WEST', 8);
////
////define('EAST_TURN_LEFT', 1);
////define('WEST_STRAIGHT', 2);
////define('SOUTH_TURN_LEFT', 3);
////define('NORTH_STRAIGHT', 4);
////define('WEST_TURN_LEFT', 5);
////define('EAST_STRAIGHT', 6);
////define('NORTH_TURN_LEFT', 7);
////define('SOUTH_STRAIGHT', 8);
////define('WEST_TURN_RIGHT', 9);
////define('NORTH_TURN_RIGHT', 10);
////define('EAST_TURN_RIGHT', 11);
////define('SOUTH_TURN_RIGHT', 12);
////define('WEST_TURN_ROUND', 13);
////define('NORTH_TURN_ROUND', 14);
////define('EAST_TURN_ROUND', 15);
////define('SOUTH_TURN_ROUND', 16);
////define('SOUTH_WEST_TURN_LEFT', 17);
////define('NORTH_WEST_TURN_LEFT', 18);
////define('NORTH_EAST_TURN_LEFT', 19);
////define('SOUTH_EAST_TURN_LEFT', 20);
////define('SOUTH_WEST_STRAIGHT', 21);
////define('NORTH_WEST_STRAIGHT', 22);
////define('NORTH_EAST_STRAIGHT', 23);
////define('SOUTH_EAST_STRAIGHT', 24);
////define('SOUTH_WEST_TURN_RIGHT', 25);
////define('NORTH_WEST_TURN_RIGHT', 26);
////define('NORTH_EAST_TURN_RIGHT', 27);
////define('SOUTH_EAST_TURN_RIGHT', 28);
////define('SOUTH_WEST_TURN_ROUND', 29);
////define('NORTH_WEST_TURN_ROUND', 30);
////define('NORTH_EAST_TURN_ROUND', 31);
////define('SOUTH_EAST_TURN_ROUND', 32);
////
////
////function rule($in, $out)
////{
////    if($in < 0 || $in > 360 || $out < 0 || $out > 360)
////        return false;
////    $direction = direction($in);
////    $action = action($in, $out);
////    return constant($direction . '_' . $action);
////}
////
////function direction($link)
////{
////    $BASE = 22.5;
////
////    $link /= $BASE;
////
////    if ($link < 1 || $link >= 15) return 'NORTH';
////    elseif ($link >= 1 && $link < 3) return 'NORTH_EAST';
////    elseif ($link >= 3 && $link < 5) return 'EAST';
////    elseif ($link >= 5 && $link < 7) return 'SOUTH_EAST';
////    elseif ($link >= 7 && $link < 9) return 'SOUTH';
////    elseif ($link >= 9 && $link < 11) return 'SOUTH_WEST';
////    elseif ($link >= 11 && $link < 13) return 'WEST';
////    elseif ($link >= 13 && $link < 15) return 'NORTH_WEST';
////    return false;
////}
////
////function action($in, $out)
////{
////    //规则
////    $rule = [
////        -315 => 'STRAIGHT',
////        -200 => 'TURN_LEFT',
////        -160 => 'TURN_ROUND',
////        -45 => 'TURN_RIGHT',
////        45  => 'STRAIGHT',
////        160 => 'TURN_LEFT',
////        200 => 'TURN_ROUND',
////        315 => 'TURN_RIGHT',
////        360 => 'STRAIGHT',
////    ];
////
////    $diff = $in - $out;
////    foreach ($rule as $key => $value)
////        if($diff <= $key)
////            return $value;
////    return false;
////}


$a = json_decode('{"plan_id":3,"stage":[{"allred_length":0,"ring_id":2,"start_time":0,"green_min":2,"num":1,"green_length":2,"movements":[{"flow":{"comment":"","logic_flow_id":"2017030116_i_408646450_2017030116_o_74244330","type":0},"channel":"99"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_74244310_2017030116_o_74244330","type":0},"channel":"99"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_390563411_2017030116_o_74246681","type":0},"channel":"35"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_166390070_2017030116_o_74246681","type":0},"channel":"35"}],"phase_seq":10,"yellow_length":3,"phase_id":1,"channel":["99"],"green_max":60},{"allred_length":0,"ring_id":2,"start_time":5,"green_min":7,"num":2,"green_length":93,"movements":[{"flow":{"comment":"","logic_flow_id":"","type":0},"channel":"77"},{"flow":{"comment":"","logic_flow_id":"","type":0},"channel":"13"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_408646450_2017030116_o_603227610","type":0},"channel":"98"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_74244310_2017030116_o_74244360","type":0},"channel":"98"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_74244310_2017030116_o_603227610","type":0},"channel":"98"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_408646450_2017030116_o_74244360","type":0},"channel":"98"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_408646450_2017030116_o_74244330","type":0},"channel":"99"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_74244310_2017030116_o_74244330","type":0},"channel":"99"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_166390070_2017030116_o_74304360","type":0},"channel":"34"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_390563411_2017030116_o_74304360","type":0},"channel":"34"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_390563411_2017030116_o_73881241","type":0},"channel":"34"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_166390070_2017030116_o_73881241","type":0},"channel":"34"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_390563411_2017030116_o_74246681","type":0},"channel":"35"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_166390070_2017030116_o_74246681","type":0},"channel":"35"}],"phase_seq":5,"yellow_length":3,"phase_id":5,"channel":["77"],"green_max":60},{"allred_length":0,"ring_id":1,"start_time":101,"green_min":7,"num":3,"green_length":11,"movements":[{"flow":{"comment":"","logic_flow_id":"2017030116_i_74244310_2017030116_o_74246681","type":0},"channel":"97"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_408646450_2017030116_o_74246681","type":0},"channel":"97"},{"flow":{"comment":"","logic_flow_id":"","type":0},"channel":"77"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_408646450_2017030116_o_603227610","type":0},"channel":"98"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_74244310_2017030116_o_74244360","type":0},"channel":"98"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_74244310_2017030116_o_603227610","type":0},"channel":"98"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_408646450_2017030116_o_74244360","type":0},"channel":"98"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_408646450_2017030116_o_74244330","type":0},"channel":"99"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_74244310_2017030116_o_74244330","type":0},"channel":"99"}],"phase_seq":2,"yellow_length":3,"phase_id":9,"channel":["97"],"green_max":60},{"allred_length":0,"ring_id":2,"start_time":115,"green_min":7,"num":4,"green_length":28,"movements":[{"flow":{"comment":"","logic_flow_id":"2017030116_i_166390070_2017030116_o_74244330","type":0},"channel":"33"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_390563411_2017030116_o_74244330","type":0},"channel":"33"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_74244310_2017030116_o_74246681","type":0},"channel":"97"},{"flow":{"comment":"","logic_flow_id":"2017030116_i_408646450_2017030116_o_74246681","type":0},"channel":"97"}],"phase_seq":6,"yellow_length":3,"phase_id":10,"channel":["33"],"green_max":60},{"allred_length":0,"ring_id":2,"start_time":146,"green_min":7,"num":5,"green_length":55,"movements":[{"flow":{"comment":"","logic_flow_id":"","type":0},"channel":""},{"flow":{"comment":"","logic_flow_id":"2017030116_i_166300130_2017030116_o_74244330","type":0},"channel":"2"},{"flow":{"comment":"","logic_flow_id":"","type":0},"channel":"109"}],"phase_seq":8,"yellow_length":3,"phase_id":11,"channel":[""],"green_max":60},{"allred_length":0,"ring_id":2,"start_time":204,"green_min":7,"num":6,"green_length":33,"movements":[{"flow":{"comment":"","logic_flow_id":"2017030116_i_74481131_2017030116_o_74246681","type":0},"channel":"66"},{"flow":{"comment":"","logic_flow_id":"","type":0},"channel":"45"},{"flow":{"comment":"","logic_flow_id":"","type":0},"channel":""}],"phase_seq":7,"yellow_length":3,"phase_id":14,"channel":["66"],"green_max":60}],"extra_time":{"tod_end_time":"19:15:00","cycle":240,"tod_start_time":"16:30:00","offset":0},"movement_timing":[{"movement_id":"13","channel":"13","phase_id":5,"phase_seq":1,"ring_id":1,"timing":[{"state":1,"start_time":5,"duration":73,"max":60,"min":7},{"state":2,"start_time":78,"duration":3},{"state":4,"start_time":81,"duration":0}],"flow":{"type":0,"logic_flow_id":"","comment":""}},{"movement_id":"77","channel":"77","phase_id":8,"phase_seq":5,"ring_id":2,"timing":[{"state":1,"start_time":5,"duration":83,"max":60,"min":7},{"state":2,"start_time":88,"duration":3},{"state":4,"start_time":91,"duration":0}],"flow":{"type":0,"logic_flow_id":"","comment":""}},{"movement_id":"","channel":"","phase_id":11,"phase_seq":8,"ring_id":2,"timing":[{"state":1,"start_time":124,"duration":33,"max":60,"min":7},{"state":2,"start_time":157,"duration":3},{"state":4,"start_time":160,"duration":0}],"flow":{"type":0,"logic_flow_id":"","comment":""}},{"movement_id":"45","channel":"45","phase_id":13,"phase_seq":4,"ring_id":1,"timing":[{"state":1,"start_time":124,"duration":33,"max":60,"min":7},{"state":2,"start_time":157,"duration":3},{"state":4,"start_time":160,"duration":0}],"flow":{"type":0,"logic_flow_id":"","comment":""}},{"movement_id":"109","channel":"109","phase_id":15,"phase_seq":7,"ring_id":2,"timing":[{"state":1,"start_time":160,"duration":47,"max":60,"min":7},{"state":2,"start_time":207,"duration":3},{"state":4,"start_time":210,"duration":0}],"flow":{"type":0,"logic_flow_id":"","comment":""}},{"movement_id":"","channel":"","phase_id":16,"phase_seq":3,"ring_id":1,"timing":[{"state":1,"start_time":160,"duration":47,"max":60,"min":7},{"state":2,"start_time":207,"duration":3},{"state":4,"start_time":210,"duration":0}],"flow":{"type":0,"logic_flow_id":"","comment":""}},{"movement_id":"35","channel":"35","phase_id":1,"phase_seq":9,"ring_id":1,"timing":[{"state":1,"start_time":0,"duration":2,"max":60,"min":2},{"state":2,"start_time":2,"duration":3},{"state":4,"start_time":5,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_166390070_2017030116_o_74246681","comment":"\u4e1c\u53f3"}},{"movement_id":"35","channel":"35","phase_id":1,"phase_seq":9,"ring_id":1,"timing":[{"state":1,"start_time":0,"duration":2,"max":60,"min":2},{"state":2,"start_time":2,"duration":3},{"state":4,"start_time":5,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_390563411_2017030116_o_74246681","comment":"\u4e1c\u53f3"}},{"movement_id":"99","channel":"99","phase_id":2,"phase_seq":10,"ring_id":2,"timing":[{"state":1,"start_time":0,"duration":2,"max":60,"min":2},{"state":2,"start_time":2,"duration":3},{"state":4,"start_time":5,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_74244310_2017030116_o_74244330","comment":"\u897f\u53f3"}},{"movement_id":"99","channel":"99","phase_id":2,"phase_seq":10,"ring_id":2,"timing":[{"state":1,"start_time":0,"duration":2,"max":60,"min":2},{"state":2,"start_time":2,"duration":3},{"state":4,"start_time":5,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_408646450_2017030116_o_74244330","comment":"\u897f\u53f3"}},{"movement_id":"34","channel":"34","phase_id":3,"phase_seq":1,"ring_id":1,"timing":[{"state":1,"start_time":5,"duration":73,"max":60,"min":7},{"state":2,"start_time":78,"duration":3},{"state":4,"start_time":81,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_390563411_2017030116_o_73881241","comment":"\u4e1c\u76f4"}},{"movement_id":"34","channel":"34","phase_id":3,"phase_seq":1,"ring_id":1,"timing":[{"state":1,"start_time":5,"duration":73,"max":60,"min":7},{"state":2,"start_time":78,"duration":3},{"state":4,"start_time":81,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_166390070_2017030116_o_74304360","comment":"\u4e1c\u76f4"}},{"movement_id":"34","channel":"34","phase_id":3,"phase_seq":1,"ring_id":1,"timing":[{"state":1,"start_time":5,"duration":73,"max":60,"min":7},{"state":2,"start_time":78,"duration":3},{"state":4,"start_time":81,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_166390070_2017030116_o_73881241","comment":"\u4e1c\u76f4"}},{"movement_id":"34","channel":"34","phase_id":3,"phase_seq":1,"ring_id":1,"timing":[{"state":1,"start_time":5,"duration":73,"max":60,"min":7},{"state":2,"start_time":78,"duration":3},{"state":4,"start_time":81,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_390563411_2017030116_o_74304360","comment":"\u4e1c\u76f4"}},{"movement_id":"35","channel":"35","phase_id":4,"phase_seq":1,"ring_id":1,"timing":[{"state":1,"start_time":5,"duration":73,"max":60,"min":7},{"state":2,"start_time":78,"duration":3},{"state":4,"start_time":81,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_166390070_2017030116_o_74246681","comment":"\u4e1c\u53f3"}},{"movement_id":"35","channel":"35","phase_id":4,"phase_seq":1,"ring_id":1,"timing":[{"state":1,"start_time":5,"duration":73,"max":60,"min":7},{"state":2,"start_time":78,"duration":3},{"state":4,"start_time":81,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_390563411_2017030116_o_74246681","comment":"\u4e1c\u53f3"}},{"movement_id":"98","channel":"98","phase_id":6,"phase_seq":5,"ring_id":2,"timing":[{"state":1,"start_time":5,"duration":83,"max":60,"min":7},{"state":2,"start_time":88,"duration":3},{"state":4,"start_time":91,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_408646450_2017030116_o_603227610","comment":"\u897f\u76f4"}},{"movement_id":"98","channel":"98","phase_id":6,"phase_seq":5,"ring_id":2,"timing":[{"state":1,"start_time":5,"duration":83,"max":60,"min":7},{"state":2,"start_time":88,"duration":3},{"state":4,"start_time":91,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_74244310_2017030116_o_74244360","comment":"\u897f\u76f4"}},{"movement_id":"98","channel":"98","phase_id":6,"phase_seq":5,"ring_id":2,"timing":[{"state":1,"start_time":5,"duration":83,"max":60,"min":7},{"state":2,"start_time":88,"duration":3},{"state":4,"start_time":91,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_74244310_2017030116_o_603227610","comment":"\u897f\u76f4"}},{"movement_id":"98","channel":"98","phase_id":6,"phase_seq":5,"ring_id":2,"timing":[{"state":1,"start_time":5,"duration":83,"max":60,"min":7},{"state":2,"start_time":88,"duration":3},{"state":4,"start_time":91,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_408646450_2017030116_o_74244360","comment":"\u897f\u76f4"}},{"movement_id":"99","channel":"99","phase_id":7,"phase_seq":5,"ring_id":2,"timing":[{"state":1,"start_time":5,"duration":83,"max":60,"min":7},{"state":2,"start_time":88,"duration":3},{"state":4,"start_time":91,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_74244310_2017030116_o_74244330","comment":"\u897f\u53f3"}},{"movement_id":"99","channel":"99","phase_id":7,"phase_seq":5,"ring_id":2,"timing":[{"state":1,"start_time":5,"duration":83,"max":60,"min":7},{"state":2,"start_time":88,"duration":3},{"state":4,"start_time":91,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_408646450_2017030116_o_74244330","comment":"\u897f\u53f3"}},{"movement_id":"97","channel":"97","phase_id":9,"phase_seq":2,"ring_id":1,"timing":[{"state":1,"start_time":81,"duration":40,"max":60,"min":7},{"state":2,"start_time":121,"duration":3},{"state":4,"start_time":124,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_74244310_2017030116_o_74246681","comment":"\u897f\u5de6"}},{"movement_id":"97","channel":"97","phase_id":9,"phase_seq":2,"ring_id":1,"timing":[{"state":1,"start_time":81,"duration":40,"max":60,"min":7},{"state":2,"start_time":121,"duration":3},{"state":4,"start_time":124,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_408646450_2017030116_o_74246681","comment":"\u897f\u5de6"}},{"movement_id":"33","channel":"33","phase_id":10,"phase_seq":6,"ring_id":2,"timing":[{"state":1,"start_time":91,"duration":30,"max":60,"min":7},{"state":2,"start_time":121,"duration":3},{"state":4,"start_time":124,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_390563411_2017030116_o_74244330","comment":"\u4e1c\u5de6"}},{"movement_id":"33","channel":"33","phase_id":10,"phase_seq":6,"ring_id":2,"timing":[{"state":1,"start_time":91,"duration":30,"max":60,"min":7},{"state":2,"start_time":121,"duration":3},{"state":4,"start_time":124,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_166390070_2017030116_o_74244330","comment":"\u4e1c\u5de6"}},{"movement_id":"66","channel":"66","phase_id":12,"phase_seq":4,"ring_id":1,"timing":[{"state":1,"start_time":124,"duration":33,"max":60,"min":7},{"state":2,"start_time":157,"duration":3},{"state":4,"start_time":160,"duration":0}],"flow":{"type":0,"logic_flow_id":"2018031110_i_331525940_2017030116_o_73881241","comment":"\u4e1c\u76f4"}},{"movement_id":"66","channel":"66","phase_id":12,"phase_seq":4,"ring_id":1,"timing":[{"state":1,"start_time":124,"duration":33,"max":60,"min":7},{"state":2,"start_time":157,"duration":3},{"state":4,"start_time":160,"duration":0}],"flow":{"type":0,"logic_flow_id":"2018031110_i_331525940_2017030116_o_74304360","comment":"\u4e1c\u76f4"}},{"movement_id":"66","channel":"66","phase_id":12,"phase_seq":4,"ring_id":1,"timing":[{"state":1,"start_time":124,"duration":33,"max":60,"min":7},{"state":2,"start_time":157,"duration":3},{"state":4,"start_time":160,"duration":0}],"flow":{"type":0,"logic_flow_id":"2018031110_i_331525940_2017030116_o_74246681","comment":"\u4e1c\u76f4"}},{"movement_id":"66","channel":"66","phase_id":12,"phase_seq":4,"ring_id":1,"timing":[{"state":1,"start_time":124,"duration":33,"max":60,"min":7},{"state":2,"start_time":157,"duration":3},{"state":4,"start_time":160,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_74481131_2017030116_o_74304360","comment":"\u4e1c\u5de6"}},{"movement_id":"66","channel":"66","phase_id":12,"phase_seq":4,"ring_id":1,"timing":[{"state":1,"start_time":124,"duration":33,"max":60,"min":7},{"state":2,"start_time":157,"duration":3},{"state":4,"start_time":160,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_74481131_2017030116_o_73881241","comment":"\u4e1c\u5de6"}},{"movement_id":"66","channel":"66","phase_id":12,"phase_seq":4,"ring_id":1,"timing":[{"state":1,"start_time":124,"duration":33,"max":60,"min":7},{"state":2,"start_time":157,"duration":3},{"state":4,"start_time":160,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_74481131_2017030116_o_74246681","comment":"\u4e1c\u76f4"}},{"movement_id":"2","channel":"2","phase_id":14,"phase_seq":7,"ring_id":2,"timing":[{"state":1,"start_time":160,"duration":47,"max":60,"min":7},{"state":2,"start_time":207,"duration":3},{"state":4,"start_time":210,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_166300130_2017030116_o_74244360","comment":"\u5317\u5de6"}},{"movement_id":"2","channel":"2","phase_id":14,"phase_seq":7,"ring_id":2,"timing":[{"state":1,"start_time":160,"duration":47,"max":60,"min":7},{"state":2,"start_time":207,"duration":3},{"state":4,"start_time":210,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_166300130_2017030116_o_603227610","comment":"\u5317\u5de6"}},{"movement_id":"2","channel":"2","phase_id":14,"phase_seq":7,"ring_id":2,"timing":[{"state":1,"start_time":160,"duration":47,"max":60,"min":7},{"state":2,"start_time":207,"duration":3},{"state":4,"start_time":210,"duration":0}],"flow":{"type":0,"logic_flow_id":"2017030116_i_166300130_2017030116_o_74244330","comment":"\u5317\u76f4"}}]}', true);
unset($a['stage']);
dd(array_column($a['movement_timing'], 'flow'));