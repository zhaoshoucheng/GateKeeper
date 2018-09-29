<?php

namespace Didi\Cloud\ItsMap\Supports;

use Didi\Cloud\ItsMap\Services\RoadNet;
use Didi\Cloud\ItsMap\Services\Log;
use Didi\Cloud\ItsMap\Supports\Coordinate;

class FlowShift {

    public function func($flows, $version) {
        if (empty($flows)) {
            return array();
        }

        $flows = $this->group_sort($flows);
        // print_r($flows);

        $roadNet = new RoadNet();
        $links = $roadNet->linkQuery($version, array_keys($flows));

        $linkIdKeys = [];
        $res = '';
        $all = array();
        foreach ($links as $link) {
            $linkIdKeys[$link->link_id] = [
                's_node' => [
                    'node_id' => $link->s_node->node_id,
                    'lng' => Coordinate::formatManual($link->s_node->lng),
                    'lat' => Coordinate::formatManual($link->s_node->lat),
                ],
                'e_node' => [
                    'node_id' => $link->e_node->node_id,
                    'lng' => Coordinate::formatManual($link->e_node->lng),
                    'lat' => Coordinate::formatManual($link->e_node->lat),
                ],
            ];
            $origin_start_pt = array($linkIdKeys[$link->link_id]['s_node']['lng'], $linkIdKeys[$link->link_id]['s_node']['lat']); 
            $origin_end_pt = array($linkIdKeys[$link->link_id]['e_node']['lng'], $linkIdKeys[$link->link_id]['e_node']['lat']);
            // 固定50m
            $pts = $this->transformLineToFixLen($origin_start_pt, $origin_end_pt, 50 * 0.00001);
            $start_pt = $pts['start_pt'];
            $end_pt = $pts['end_pt'];
            // 为了美观内收10m
            $pts = $this->transformLineToFixLen($end_pt, $start_pt, 40 * 0.00001);
            $linkIdKeys[$link->link_id]['start_pt'] = $pts['end_pt'];
            $linkIdKeys[$link->link_id]['end_pt'] = $pts['start_pt'];
        }

        $offset = 0.00005;
        foreach ($flows as $values) {
            $cnt = count($values);
            if ($cnt > 1 and abs($values[$cnt-1]['turn_degree'] - 180) <= 5) {
                $t = array_pop($values);
                array_unshift($values, $t);
            }
            $order = ($cnt - 1) * 1.0 / 2;

            foreach ($values as $flow) {
                $logic_flow_id = $flow['logic_flow_id'];
                $mapFlows = array();
                $degree = $flow['indegree'] - 90;
                $degree = 360 - ($degree - 90);

                $dist = $order * $offset;

                $lng = $linkIdKeys[$flow['inlink']]['start_pt'][0];
                $lat = $linkIdKeys[$flow['inlink']]['start_pt'][1];
                $start = $this->shiftNode($lng, $lat, $degree, $dist);
                $res .= $start['lng'] . ',' . $start['lat'] . ';';
                $mapFlows[] = [$start['lng'], $start['lat']];

                $lng = $linkIdKeys[$flow['inlink']]['end_pt'][0];
                $lat = $linkIdKeys[$flow['inlink']]['end_pt'][1];
                $end = $this->shiftNode($lng, $lat, $degree, $dist);
                
                $points = $this->cutLine(array($start['lng'], $start['lat']), array($end['lng'], $end['lat']));
                foreach ($points as $point) {
                    $mapFlows[] = $point;
                    $res .= $point[0] . ',' . $point[1] . ';';
                }

                $mapFlows[] = [$end['lng'], $end['lat']];
                $res .= $end['lng'] . ',' . $end['lat'] . ';';

                if (abs($flow['turn_degree'] - 180) <= 5) {
                    // 掉头
                    $lng = $end['lng'];
                    $lat = $end['lat'];
                    $degree = 360 - ($flow['indegree'] - 90);
                    $degree = $degree + 90;
                    $ret = $this->shiftNode($lng, $lat, $degree, $offset - 0.00002);
                    $res .= $ret['lng'] . ',' . $ret['lat'] . ';';
                    $mapFlows[] = [$ret['lng'], $ret['lat']];

                    $degree = $degree + 90;
                    $ret = $this->shiftNode($ret['lng'], $ret['lat'], $degree, $offset - 0.00002);
                    $res .= $ret['lng'] . ',' . $ret['lat'] . ';';
                    $mapFlows[] = [$ret['lng'], $ret['lat']];
                } else {
                    $lng = $end['lng'];
                    $lat = $end['lat'];
                    $degree = 360 - ($flow['outdegree'] - 90);
                    $ret = $this->shiftNode($lng, $lat, $degree, $offset - 0.00002);
                    $res .= $ret['lng'] . ',' . $ret['lat'] . ';';
                    $mapFlows[] = [$ret['lng'], $ret['lat']];
                }
                

                $order -= 1;
                
                $all[] = array (
                    'logic_flow_id' => $logic_flow_id,
                    'flows' => $mapFlows,
                );
            }
        }
        // print_r($linkIdKeys);
        // print_r($res);
        Log::notice($res);
        return $all;
    }

    private function group_sort($flows) {
        $flow_group = array();
        foreach ($flows as $flow) {
            $flow_group[$flow['inlink']][] = $flow;
        }
        foreach ($flow_group as $key => &$flows) {
            $len = count($flows);
            $indegree = $flows[0]['indegree'];
            if ($indegree >= 180) {
                $outdegree = $indegree - 180;
            } else {
                $outdegree = $indegree + 180;
            }
            for ($i = 0; $i < $len; $i++) {
                for ($j = $len - 1; $j > $i; $j--) {
                    $degree1 = $flows[$j]['outdegree'];
                    $degree0 = $flows[$j-1]['outdegree'];
                    if ($degree1 < $outdegree) {
                        $degree1 += 360;
                    }
                    if ($degree0 < $outdegree) {
                        $degree0 += 360;
                    }
                    if ($degree1 < $degree0) {
                        $tmp = $flows[$j];
                        $flows[$j] = $flows[$j - 1];
                        $flows[$j - 1] = $tmp;
                    }
                }
            }
        }
        return $flow_group;
    }

    // move start
    private function transformLineToFixLen($start_pt, $end_pt, $target_dis) {
        $dy = $end_pt[1] - $start_pt[1];
        $dx = $end_pt[0] - $start_pt[0];
        $dis_square = $dy*$dy + $dx*$dx;
        $dis = sqrt($dis_square);
        $new_start_pt[0] = $end_pt[0] - $target_dis / $dis * $dx; 
        $new_start_pt[1] = $end_pt[1] - $target_dis / $dis * $dy;
        $new_start_pt[0] = round($new_start_pt[0], 6);
        $new_start_pt[1] = round($new_start_pt[1], 6);
        return array(
            'start_pt' => $new_start_pt,
            'end_pt'   => $end_pt,
        );
    }

    // cut line
    private function cutLine($start_pt, $end_pt, $nums = 3) {
        $points = array();
        $dy = $end_pt[1] - $start_pt[1];
        $dx = $end_pt[0] - $start_pt[0];
        for ($i = 1; $i <= $nums; $i ++) { 
            $point_pt[0] = $start_pt[0] + 1.0 * $i / ($nums + 1) * $dx; 
            $point_pt[1] = $start_pt[1] + 1.0 * $i / ($nums + 1) * $dy;
            $point_pt[0] = round($point_pt[0], 6);
            $point_pt[1] = round($point_pt[1], 6);
            $points[] = $point_pt;
        }
        return $points;
    }

    // 平移点
    private function shiftNode($lng, $lat, $degree, $dist) {
        // if ($degeee >= 360) {
        //     $degeee -= 360;
        // }
        // if ($degeee < 0) {
        //     $degeee += 360;
        // }
        // $flag = 1;
        // if ($dist < 0) {
        //     $dist = -1 * $dist;
        //     $flag = -1;
        // } 
        // if (abs($degeee - 90) <= 5) {
        //     $lat += $dist;
        //     return array(
        //         'lng' => $lng,
        //         'lat' => $lat,
        //     );
        // }
        // if (abs($degeee - 270) <= 5) {
        //     $lat -= $dist;
        //     return array (
        //         'lng' => $lng,
        //         'lat' => $lat,
        //     );
        // }
        // $k = tan(deg2rad($degree));
        // if ($degree >= 0 and $degree < 90) {
        //     $flagx = 1;
        //     $flagy = 1;
        // } elseif ($degree >= 90 and $degree < 180) {
        //     $flagx = -1;
        //     $flagy = 1;
        // } elseif ($degree >= 180 and $degree < 270) {
        //     $flagx = -1;
        //     $flagy = -1;
        // } elseif ($degree >= 270 and $degree < 360) {
        //     $flagx = 1;
        //     $flagy = -1;
        // }
        // $dx = $flagx * sqrt($dist * $dist / (1 + $k * $k));
        // $dy = $flagy * abs($k) * abs($dx);
        // $lng = $lng + $flag * $dx;
        // $lat = $lat + $flag * $dy;
        $lng = $lng + $dist * cos(deg2rad($degree));
        $lng = round($lng, 6);
        $lat = $lat + $dist * sin(deg2rad($degree));
        $lat = round($lat, 6);
        return array (
            'lng' => $lng,
            'lat' => $lat,
        );
    }

    // 下面的函数都没有使用过
    private function shiftLink($start_pt, $end_pt, $is_lng_direct = true, $sign = 1, $shift_offset = 0.00005, $len = 0, $shift_start_pt = false) {
        if (empty($shift_start_pt)) {
           // 做垂直平移效果不一定好，如果要做垂直平移，可以计算出垂直平移后的两个端点，注意垂直平移的垂足。
           // 暂时还是做水平纵向平移，可视化效果相对好点 
           // $vertical_line = $this->verticalLine($start_pt, $end_pt, $shift_offset);
           // $shift_end_pt = $vertical_line['new_end_pt']; // 这个地方的sign 需要微调,平移的方向不好确定
        }
        // 经度相同或者南北向直行link，横向平移
        if (abs($end_pt[0] - $start_pt[0]) < 0.00001 || (empty($shift_start_pt) && $is_lng_direct)) {
            if (empty($shift_start_pt)) {
                $shift_start_pt[0] = $start_pt[0] + $sign * $shift_offset;
                $shift_start_pt[1] = $start_pt[1];
                $shift_end_pt[0] = $end_pt[0] + $sign * $shift_offset;
                $shift_end_pt[1] = $end_pt[1];
            } else {
                $sign = ($end_pt[1] - $start_pt[1] > 0) ? 1 : -1;
                $shift_end_pt[0] = $shift_start_pt[0];
                $shift_end_pt[1] = $shift_start_pt[1] + $sign * $len;
            }
            return array(
                'shift_start_pt' => $shift_start_pt,
                'shift_end_pt' => $shift_end_pt,
            );
        }

        // 纬度相同或者东西向直行link，纵向平移
        if (abs($end_pt[1] - $start_pt[1]) < 0.00001 || (empty($shift_start_pt) && !$is_lng_direct)) {
            if (empty($shift_start_pt)) {
                $shift_start_pt[0] = $start_pt[0];
                $shift_start_pt[1] = $start_pt[1] + $sign * $shift_offset;
                $shift_end_pt[0] = $end_pt[0];
                $shift_end_pt[1] = $end_pt[1] + $sign * $shift_offset;
            } else {
                $sign = ($end_pt[0] - $start_pt[0] > 0) ? 1 : -1;
                $shift_end_pt[1] = $shift_start_pt[1];
                $shift_end_pt[0] = $shift_start_pt[0] + $sign * $len;
            }
            return array(
                'shift_start_pt' => $shift_start_pt,
                'shift_end_pt' => $shift_end_pt,
            );
        }

        $k = ($end_pt[1] - $start_pt[1]) / ($end_pt[0] - $start_pt[0]);
        if (empty($len)) {
            $len_square = ($end_pt[0] - $start_pt[0])*($end_pt[0] - $start_pt[0]) + ($end_pt[1] - $start_pt[1]) * ($end_pt[1] - $start_pt[1]);
        } else {
            $len_square = $len * $len;
        }
        $sign = ($end_pt[0] - $start_pt[0] > 0) ? 1 : -1;    
        $dx = sqrt($len_square / (1 + $k * $k)) * $sign;
        $dy = $k * $dx;
        if (!empty($shift_start_pt)) {
            $shift_end_pt[0] = $shift_start_pt[0] + $dx; 
            $shift_end_pt[1] = $shift_start_pt[1] + $dy; 
            $shift_end_pt[0] = round($shift_end_pt[0], 6);
            $shift_end_pt[1] = round($shift_end_pt[1], 6);
        }

        return array(
            'shift_start_pt' => $shift_start_pt,
            'shift_end_pt' => $shift_end_pt,
        );
    }

    public function getLinkInfoByIdsAndVersion($link_ids, $map_version = 0) {
        //$geo_data = $this->getLinkInfoFromDB($link_ids, $map_version);
        $geo_data = false;
        if (empty($geo_data)) {
            $geo_data = $this->getLinkInfoFromApi($link_ids, $map_version);
        }
        return $geo_data;
    }

    public function getLinkCoordsFromDB($link_ids, $map_version = 0) {
        if (empty($this->db)) {
            return false;
        }
        $new_link_ids = array();
        foreach ($link_ids as $link) {
            $link = intval($link);
            if (empty($link)) {
                continue;
            }
            $new_link_ids[] = substr($link, 0, -1);
        }
        $this->db
            ->select('id, link_id, direction, snodeid, enodeid, geom')
            ->from($this->tb)
            ->where_in('link_id', $new_link_ids);
        if (!empty($map_version)) {
            $this->db->where('start_version <=', $map_version);
            $this->db->where('stop_version >', $map_version);
        } else {
            $this->db->where('stop_version', 9999999999);
        }
        $query = $this->db->get();
        if (empty($query)) {
            return false;
        }
        $data = $query->result_array();
        if (empty($data)) {
            return array();
        }
        return $data;
    }

    public function getLinkInfoFromDB($link_ids, $map_version) {
        $data = $this->getLinkCoordsFromDB($link_ids, $map_version);
        if (empty($data)) {
            return array();
        }
        $features = array();
        foreach ($data as $row) {
            // 将每一行数据都拼一个link加两个point
            $line_feature = array();
            $spoint_feature = array();
            $epoint_feature = array();
            $coords = explode(";", $row['geom']);
            if (empty($coords)) {
                continue;
            }
            foreach ($coords as $coord) {
                $point = explode(",", $coord);
                if (count($point) != 2) {
                    continue;
                }
                $line_feature['geometry']['coordinates'][] = $point;
            }
            if (!isset($line_feature['geometry']['coordinates']) || empty($line_feature['geometry']['coordinates'])) {
                return array();
            }
            $line_feature['geometry']['type'] = 'LineString';
            $line_feature['properties'] = array(
                'id'      => $row['link_id'],
                'snodeid' => $row['snodeid'],
                'enodeid' => $row['enodeid'],
                'direction' => $row['direction'], 
                'db_id'     => $row['id'],
            );
            $line_feature['type'] = 'Feature';
            $pts_cnt = count($line_feature['geometry']['coordinates']);
            $spoint = $line_feature['geometry']['coordinates'][0];
            $epoint = $line_feature['geometry']['coordinates'][$pts_cnt - 1];
            $spoint_feature = array(
                "geometry" => array(
                    "coordinates" => $spoint,
                    "type"        => "Point",
                ),
                "properties" => array(
                    'id'        => $row['snodeid'], 
                    'db_id'     => $row['id'],
                ),
                "type"       => "Feature",
            );
            $epoint_feature = array(
                "geometry" => array(
                    "coordinates" => $epoint,
                    "type"        => "Point",
                ),
                "properties" => array(
                    'id'        => $row['enodeid'], 
                    'db_id'     => $row['id'],
                ),
                "type"       => "Feature",
            );
            $features[] = $line_feature;
            $features[] = $spoint_feature;
            $features[] = $epoint_feature;
        }

        $geo_json_arr = array(
            'features' => $features,
            'type'     => "FeatureCollection",
        );
        return $geo_json_arr;
    }
    
    public function getLinkInfoFromApi($link_ids, $map_version = '2017082514') {
        if (empty($map_version)) {
            $map_version = '2017082514';
        }
        $new_link_ids = array();
        foreach ($link_ids as $link) {
            if (empty($link)) {
                continue;
            }
            $new_link_ids[] = substr($link, 0, -1);
        }
        $post_data = array(
            'id'     => implode(",", $new_link_ids),
            'version' => $map_version,
        );
        //echo json_encode($post_data) . "\n";
        $url = "http://100.69.187.40:8080/link_query/linkid_with_node";
        $error = null;

        $resp = httpPOST($url, $post_data); 
        $data = json_decode($resp, true);
        if (empty($data) || !isset($data['features'])) {
            return array();
        }
        return $data;
    }

    public function splitGeoDataByLinkid($geo_data) {
         if (empty($geo_data)) {
            return array();
        }
        $geo_data_by_node = array();
        $geo_data_by_link = array();
        foreach ($geo_data['features'] as $geo_obj) {
            if (!isset($geo_obj['geometry']['type']) || !isset($geo_obj['properties']['id'])) {
                continue;
            }
            /*
            if ($geo_obj['geometry']['type'] != "LineString") {
                continue;
            }*/

            if ($geo_obj['geometry']['type'] == "LineString") {
                $link_id = $geo_obj['properties']['id'];
                $geo_data_by_link[$link_id] = $geo_obj;
            } else if ($geo_obj['geometry']['type'] == "Point") {
                $node_id = $geo_obj['properties']['id'];
                $geo_data_by_node[$node_id] = $geo_obj;
            }
        }
        return array(
            'geo_data_by_node' => $geo_data_by_node,
            'geo_data_by_link' => $geo_data_by_link,
        );
    }

    public function computeLinksCenter($link_ids, $link_version = 0) {
        $rect = $this->getLinksRect($link_ids, $link_version);
        if (empty($rect)) {
            return array(
                'lng' => 0,
                'lat' => 0,
            );
        }
        $min_lng = $rect['min_lng'];
        $min_lat = $rect['min_lat'];
        $max_lng = $rect['max_lng'];
        $max_lat = $rect['max_lat'];
        return array(
            'lng' => ($min_lng + $max_lng)/2,
            'lat' => ($min_lat + $max_lat)/2,
            'scale' => intval(($max_lat - $min_lat) * 10000),
        );

    }
    public function getLinksRect($link_ids, $link_version = 0) {
        $data = $this->getLinkInfoByIdsAndVersion($link_ids, $link_version);
        if (empty($data) || !isset($data['features'])) {
            return array(
            );
        }
        $points = array();
        $max_lng = 0;
        $max_lat = 0;
        $min_lng = 180;
        $min_lat = 180;
        foreach ($data['features'] as $feature) {
            // 获取link端点的数据
            if ($feature['geometry']['type'] == 'Point') {
                $point = $feature['geometry']['coordinates'];
                //echo json_encode($point) . "\n";
                if(empty($point) || empty($point[0]) || empty($point[1])) {
                    continue;
                }
                if ($point[0] > $max_lng) {
                    $max_lng = $point[0];
                }
                if ($point[0] < $min_lng) {
                    $min_lng = $point[0];
                }
                if ($point[1] > $max_lat) {
                    $max_lat = $point[1];
                }
                if ($point[1] < $min_lat) {
                    $min_lat = $point[1];
                }
            }
        }
        return array(
            'min_lng' => $min_lng,
            'min_lat' => $min_lat,
            'max_lng' => $max_lng,
            'max_lat' => $max_lat,
        );
    }
    const OUT_LINK = 0;
    const IN_LINK = 1;
    public function getFootwayLine($links_geo_json, $link, $link_type = 1) {
        // 找到enter_link的geojson
        if (empty($links_geo_json)) {
            return array();
        }
        $split_rst_by_link = $this->splitGeoDataByLinkid($links_geo_json);
        if (empty($split_rst_by_link)) {
            return array();
        }
        $geo_data_by_node = $split_rst_by_link['geo_data_by_node'];
        $geo_data_by_link = $split_rst_by_link['geo_data_by_link'];
        $format_link = substr($link, 0, -1);
        $geo_line = array();
        if (isset($geo_data_by_link[$format_link])) {
            $geo_line = $geo_data_by_link[$format_link];
        }
        if (empty($geo_line) || !isset($geo_line['geometry']['coordinates'])) {
            return array();
        }
        $direction = $this->getDirectionByLink($geo_line, $link, $link_type);
        $coordinates = $geo_line['geometry']['coordinates'];
        $segment = $this->cutLinkToSegment($link, $coordinates, $link_type);
        $origin_start_pt = $segment['start_pt'];
        $origin_end_pt = $segment['end_pt'];
        // 需要通过snode和enode确定首末点
       if ($link_type == self::IN_LINK) {
            $pts = $this->transformLineToFixLen($origin_start_pt, $origin_end_pt, 3 * 0.00001);
        } else {
            $pts = $this->transformLineToFixLen($origin_end_pt, $origin_start_pt, 3 * 0.00001);
        }
        $second_pt = $pts['start_pt'];
        $first_pt = $pts['end_pt'];
        $len = 0.00005;
        if ($link_type == self::IN_LINK) {
            $is_right = false;
        } else {
            $is_right = true;
        }
        $vertical_line = $this->verticalLine($first_pt, $second_pt, $len, $is_right);
        $third_pt = $vertical_line['new_end_pt'];
        $third_pt = array($third_pt[0], $third_pt[1]);
        $footway_polyline = array(
            $second_pt, 
            $third_pt, 
        );

        return array(
            'footway' => $footway_polyline,
            'direction' => $direction,
            'turn'    => self::RUN_DIRECT,
        );
    }

    private function cutLinkToSegment($link, $coordinates, $link_type) {
        $direct_tag = substr($link, -1, 1);
        $pts_cnt = count($coordinates);
        // direct_tag=0的坐标序列和方向一致，如果direct_tag=1则坐标序列和方向相反，需要对坐标序列转置
        if ($link_type == self::IN_LINK) {
            // 取in_link的倒数2点的长度，如果倒数2点的长度小于10m，就使用首末点 
            if ($direct_tag == 1) {
                $origin_start_pt = $coordinates[1];
                $origin_end_pt = $coordinates[0];
                $abs_dis = abs($origin_end_pt[1] -  $origin_start_pt[1]) + abs($origin_end_pt[0] -  $origin_start_pt[0]);
                if ($abs_dis < 0.0001) {
                    $origin_start_pt = $coordinates[$pts_cnt - 1];
                }
            } else {
                $origin_start_pt = $coordinates[$pts_cnt - 2];
                $origin_end_pt = $coordinates[$pts_cnt - 1];
                $abs_dis = abs($origin_end_pt[1] -  $origin_start_pt[1]) + abs($origin_end_pt[0] -  $origin_start_pt[0]);
                if ($abs_dis < 0.0001) {
                    $origin_start_pt = $coordinates[0];
                }
            }
        } else {
            // 取out_link的最开始两点或者首末两点
            if ($direct_tag == 0) {
                $origin_start_pt = $coordinates[0];
                $origin_end_pt = $coordinates[1];
                $abs_dis = abs($origin_end_pt[1] -  $origin_start_pt[1]) + abs($origin_end_pt[0] -  $origin_start_pt[0]);
                if ($abs_dis < 0.0001) {
                    $origin_end_pt = $coordinates[$pts_cnt - 1];
                }
            } else {
                $origin_start_pt = $coordinates[$pts_cnt - 1];
                $origin_end_pt = $coordinates[$pts_cnt - 2];
                $abs_dis = abs($origin_end_pt[1] -  $origin_start_pt[1]) + abs($origin_end_pt[0] -  $origin_start_pt[0]);
                if ($abs_dis < 0.0001) {
                    $origin_end_pt = $coordinates[0];
                }
            }
        }

        return array(
            'start_pt' => $origin_start_pt,
            'end_pt'   => $origin_end_pt,
        );
    }

    public function simplifyTurnLinksToLine($links_geo_json, $enter_link, $exit_link, $direction, $turn) {
        // 找到enter_link的geojson
        if (empty($links_geo_json)) {
            return array();
        }
        $split_rst_by_link = $this->splitGeoDataByLinkid($links_geo_json);
        if (empty($split_rst_by_link)) {
            return array();
        }
        $geo_data_by_node = $split_rst_by_link['geo_data_by_node'];
        $geo_data_by_link = $split_rst_by_link['geo_data_by_link'];
        $format_enter_link = substr($enter_link, 0, -1);
        $geo_line = array();
        $geo_start_pt = array();
        $geo_end_pt = array();
        if (isset($geo_data_by_link[$format_enter_link])) {
            $geo_line = $geo_data_by_link[$format_enter_link];
            /* 
            if (isset($geo_line['properties']['snodeid']) && isset($geo_line['properties']['enodeid'])) {
                $geo_snodeid = $geo_line['properties']['snodeid'];
                $geo_enodeid = $geo_line['properties']['enodeid'];
                if (isset($geo_data_by_node[$geo_snodeid])) {
                    $geo_start_pt = $geo_data_by_node[$geo_snodeid];
                }

                if (isset($geo_data_by_node[$geo_enodeid])) {
                    $geo_end_pt = $geo_data_by_node[$geo_enodeid];
                }
            }*/
        }

        $format_exit_link = substr($exit_link, 0, -1);
        $next_geo_line = array();
        if (isset($geo_data_by_link[$format_exit_link])) {
            $next_geo_line = $geo_data_by_link[$format_exit_link];
        }

        $shift_offset = 0.00004;
        $poly_line = array();
        // 1. 缩短直行线
        // 2. 横向或纵向平移线
        // 3. 平移左转link
        if (!empty($geo_line)) {
            $coordinates = $geo_line['geometry']['coordinates'];
            $seg = $this->cutLinkToSegment($enter_link, $coordinates, self::IN_LINK);
            $origin_start_pt = $seg['start_pt']; 
            $origin_end_pt = $seg['end_pt']; 
            $pts = $this->transformLineToFixLen($origin_start_pt, $origin_end_pt, 50 * 0.00001);
            $start_pt = $pts['start_pt'];
            $end_pt = $pts['end_pt'];
            // 为了美观内收10m
            $pts = $this->transformLineToFixLen($end_pt, $start_pt, 40 * 0.00001);
            $start_pt = $pts['end_pt'];
            $end_pt = $pts['start_pt'];
            if ($direction < 0) {
                $direct_info = $this->computeDirectTurnByLinksInfo($enter_link, $geo_line, $exit_link, $next_geo_line);
                $direction = $direct_info['direction'];
                $turn = $direct_info['turn'];
            }
            
            // 左转或右转
            if ($direction == self::WEST) {
                $is_lng_direct = false;
                $sign = ($turn == self::TURN_RIGHT) ? -1 : 1;
            } else if ($direction == self::EAST) {
                $is_lng_direct = false;
                $sign = ($turn == self::TURN_RIGHT) ? 1 : -1;
            } else if ($direction == self::NORTH) {
                $is_lng_direct = true;
                $sign = ($turn == self::TURN_RIGHT) ? -1 : 1;
            } else if ($direction == self::SOUTH) {
                $is_lng_direct = true;
                $sign = ($turn == self::TURN_RIGHT) ? 1 : -1;
            }
            // 先将enter_link进行横向或纵向平移，再将exit_link平移到enter_link的末尾处
            if ($turn == self::TURN_LEFT || $turn == self::TURN_RIGHT) {
                $shift_line = $this->shiftLink($start_pt, $end_pt, $is_lng_direct, $sign, $shift_offset);
                $first_pt = $shift_line['shift_start_pt'];
                $second_pt = $shift_line['shift_end_pt'];
                $third_pt = $second_pt;
                $len = 0.00005;
                if (!empty($next_geo_line)) {
                    $coordinates = $next_geo_line['geometry']['coordinates'];
                    $next_seg = $this->cutLinkToSegment($exit_link, $coordinates, self::OUT_LINK);
                    $next_start_pt = $next_seg['start_pt']; 
                    $next_end_pt = $next_seg['end_pt']; 
                    $turn_line = $this->shiftLink($next_start_pt, $next_end_pt, $is_lng_direct, $sign, $shift_offset, $len, $second_pt);
                    $third_pt = $turn_line['shift_end_pt'];
                } else {
                    if ($is_lng_direct) {
                        $third_pt[0] = $second_pt[0] + $sign * $len; 
                    } else {
                        $third_pt[1] = $second_pt[1] + $sign * $len; 
                    }
                }
                $third_pt = array($third_pt[0], $third_pt[1]);
                $poly_line = array($first_pt, $second_pt, $third_pt);
            } else if ($turn == self::RUN_DIRECT) {
                $poly_line = array($start_pt, $end_pt);
            } else if ($turn == self::TURN_ROUND) {
                $shift_line = $this->shiftLink($start_pt, $end_pt, $is_lng_direct, $sign, $shift_offset*2);
                $start_pt = $shift_line['shift_start_pt'];
                $end_pt = $shift_line['shift_end_pt'];
                $pts = $this->transformLineToFixLen($end_pt, $start_pt, 35 * 0.00001);
                $first_pt = $pts['end_pt'];
                $second_pt = $pts['start_pt'];
                $vertical_line = $this->verticalLine($first_pt, $second_pt, $shift_offset);
                $third_pt = $vertical_line['new_end_pt'];
                $turn_line = $this->shiftLink($second_pt, $first_pt, $is_lng_direct, $sign, $shift_offset, 1.5*$shift_offset, $third_pt);
                $fouth_pt = $turn_line['shift_end_pt'];
                $fouth_pt = array($fouth_pt[0], $fouth_pt[1]);
                $poly_line = array($first_pt, $second_pt, $third_pt, $fouth_pt);
                // 主要是要做平移
            } else {
                $poly_line = array();
            }
        }
        return array(
            'direction' => $direction,
            'turn'  => $turn,    
            'poly_line' => $poly_line,
        );
    }

    

    //计算[$start_pt, $end_pt]的往左的垂直线，垂足是end_pt 
    private function verticalLine($start_pt, $end_pt, $vertical_line_len, $is_right = false) {
        // 左转或右转
        if (abs($end_pt[0] - $start_pt[0]) < 0.00001) {
            $sign = ($end_pt[1] - $start_pt[1] > 0) ? -1 : 1;
            $sign = $is_right ? -1*$sign : $sign; 
            return array(
                'new_start_pt' => $end_pt,
                'new_end_pt'   => array(
                    $end_pt[0] + $sign * $vertical_line_len,
                    $end_pt[1],
                )
            );
        }

        if (abs($end_pt[1] - $start_pt[1]) < 0.00001) {
            $sign = ($end_pt[0] - $start_pt[0] > 0) ? 1 : -1;
            $sign = $is_right ? -1*$sign : $sign; 
            return array(
                'new_start_pt' => $end_pt,
                'new_end_pt'   => array(
                    $end_pt[0],
                    $end_pt[1] + $sign * $vertical_line_len,
                )
            );
        }
        $k = -($end_pt[0] - $start_pt[0]) / ($end_pt[1] - $start_pt[1]);
        $len_square = $vertical_line_len * $vertical_line_len;
        $dy_sign = ($end_pt[0] - $start_pt[0] > 0) ? 1 : -1;
        $dx_sign = ($end_pt[1] - $start_pt[1] > 0) ? -1 : 1; 
        if ($is_right) {
            $dx_sign = -1*$dx_sign;
            $dy_sign = -1*$dy_sign;
        }
        $dx = sqrt($len_square / (1 + $k * $k)) * $dx_sign;
        $dy = $k * $dx;
        $new_end_pt[0] = $end_pt[0] + $dx; 
        $new_end_pt[1] = $end_pt[1] + $dy; 
        $new_end_pt[0] = round($new_end_pt[0], 6);
        $new_end_pt[1] = round($new_end_pt[1], 6);

        return array(
            'new_start_pt' => $end_pt,
            'new_end_pt'   => $new_end_pt,
        ); 
    }

    const EAST = 1;
    const WEST = 2;
    const SOUTH = 3;
    const NORTH = 4;
    const RUN_DIRECT = 0;
    const TURN_RIGHT = 1; 
    const TURN_LEFT = 2;
    const TURN_ROUND = 3;
    private function computeDirectTurnByLinksInfo($enter_link, $geo_line, $exit_link, $next_geo_line) {
        $enter_link_direction = $this->getDirectionByLink($geo_line, $enter_link, self::IN_LINK);
        $exit_link_direction = $this->getDirectionByLink($next_geo_line, $exit_link, self::OUT_LINK);
        $link_turn_map = array(
            self::EAST => array(
                array(self::EAST, self::EAST, self::RUN_DIRECT),
                array(self::EAST, self::WEST, self::TURN_ROUND),
                array(self::EAST, self::NORTH, self::TURN_LEFT),
                array(self::EAST, self::SOUTH, self::TURN_RIGHT),
            ),
            self::WEST => array( 
                array(self::WEST, self::WEST, self::RUN_DIRECT),
                array(self::WEST, self::EAST, self::TURN_ROUND),
                array(self::WEST, self::SOUTH, self::TURN_LEFT),
                array(self::WEST, self::NORTH, self::TURN_RIGHT),
            ),
            self::SOUTH => array(
                array(self::SOUTH, self::SOUTH, self::RUN_DIRECT),
                array(self::SOUTH, self::NORTH, self::TURN_ROUND),
                array(self::SOUTH, self::EAST, self::TURN_LEFT),
                array(self::SOUTH, self::WEST, self::TURN_RIGHT),
            ),
            self::NORTH => array(
                array(self::NORTH, self::NORTH, self::RUN_DIRECT),
                array(self::NORTH, self::SOUTH, self::TURN_ROUND),
                array(self::NORTH, self::WEST, self::TURN_LEFT), 
                array(self::NORTH, self::EAST, self::TURN_RIGHT),
            ),
        );
        if ($enter_link_direction > 0 && $exit_link_direction > 0) {
            $direction = $enter_link_direction;
            if (!isset($link_turn_map[$direction])) {
                $direction = -1;
                $turn = -1;
            } else {
                $link_turns = $link_turn_map[$direction];
                $turn = -1;
                foreach ($link_turns as $link_turn) {
                    if ($exit_link_direction == $link_turn[1]) {
                        $turn = $link_turn[2];
                    }
                }
            }
        }
        return array(
            'direction' => $direction,
            'turn' => $turn,
        );
    }

    private function getDirectionByLink($geo_line, $link, $link_type = self::IN_LINK) {
        $line_direct = -1;
        if (!empty($geo_line)) {
            $coordinates = $geo_line['geometry']['coordinates'];
            // 需要通过snode和enode确定首末点

            $segment = $this->cutLinkToSegment($link, $coordinates, $link_type);
            $origin_start_pt = $segment['start_pt'];
            $origin_end_pt = $segment['end_pt'];
            if (abs($origin_end_pt[1] - $origin_start_pt[1]) < abs($origin_end_pt[0] - $origin_start_pt[0])) {
                if ($origin_end_pt[0] > $origin_start_pt[0]) {
                    $line_direct = self::WEST;
                } else {
                    $line_direct = self::EAST;
                }
            } else {
                if ($origin_end_pt[1] > $origin_start_pt[1]) {
                    $line_direct = self::SOUTH;
                } else {
                    $line_direct = self::NORTH;
                }
            }
        }
        return $line_direct;

    }

}
