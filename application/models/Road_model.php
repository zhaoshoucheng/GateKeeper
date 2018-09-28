<?php
/********************************************
# desc:    干线据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-08-21
********************************************/

class Road_model extends CI_Model
{
    private $tb = 'road';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        // 判断数据表是否存在
        if (!$this->isTableExisted($this->tb)) {
            return [];
        }

        $this->load->model('waymap_model');
        $this->load->model('redis_model');
    }

    /**
     * 查询干线列表
     * @param $cityId interger Y 城市ID
     * @return array
     */
    public function queryRoadList($cityId)
    {
        if (intval($cityId) < 1) {
            return [];
        }

        $where = 'city_id = ' . $cityId . ' and is_delete = 0';
        $this->db->select('road_id, road_name, road_direction');
        $this->db->from($this->tb);
        $this->db->where($where);
        $this->db->order_by('created_at desc');
        $res = $this->db->get()->result_array();
        if (empty($res)) {
            return [];
        }

        return $res;

    }

    /**
     * 新增干线
     * @param $data['city_id']        interger Y 城市ID
     * @param $data['road_name']      string   Y 干线名称
     * @param $data['junction_ids']   array    Y 干线路口ID
     * @param $data['road_direction'] interger Y 干线方向 1：东西 2：南北
     * @return array
     */
    public function addRoad($data)
    {
        if (empty($data)) {
            return ['errno' => 0, 'errmsg' => ''];
        }

        // 判断干线名称是否已存在
        if ($this->isRoadNameExisted($data['road_name'])) {
            return ['errno' => -1, 'errmsg' => '干线名称已存在！'];
        }

        $insertData = [
            'city_id'            => intval($data['city_id']),
            'road_id'            => md5(implode(',', $data['junction_ids']) . $data['road_name']),
            'road_name'          => strip_tags(trim($data['road_name'])),
            'logic_junction_ids' => implode(',', $data['junction_ids']),
            'road_direction'     => intval($data['road_direction']),
            'user_id'            => 0,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ];

        $res = $this->db->insert($this->tb, $insertData);
        if (!$res) {
            return ['errno' => -1, 'errmsg' => '新增干线入库失败！'];
        }

        return ['errno' => 0, 'errmsg' => ''];
    }

    /**
     * 编辑干线
     * @param $data['city_id']        interger Y 城市ID
     * @param $data['road_id']        string   Y 干线ID
     * @param $data['road_name']      string   Y 干线名称
     * @param $data['junction_ids']   array    Y 干线路口ID
     * @param $data['road_direction'] interger Y 干线方向 1：东西 2：南北
     * @return array
     */
    public function editRoad($data)
    {
        if (empty($data)) {
            return ['errno' => 0, 'errmsg' => ''];
        }

        // 判断干线名称是否已存在
        if ($this->isRoadNameExisted($data['road_name'], $data['road_id'])) {
            return ['errno' => -1, 'errmsg' => '干线名称已存在！'];
        }

        $where = 'road_id = "' . strip_tags(trim($data['road_id'])) . '"';
        $where .= ' and city_id = ' . intval($data['city_id']);
        $where .= ' and is_delete = 0';
        $this->db->where($where);

        $updateData = [
            'road_name'          => strip_tags(trim($data['road_name'])),
            'logic_junction_ids' => implode(',', $data['junction_ids']),
            'road_direction'     => intval($data['road_direction']),
            'updated_at'         => date('Y-m-d H:i:s'),
        ];
        $this->db->update($this->tb, $updateData);
        if ($this->db->affected_rows() < 1) {
            return ['errno' => -1, 'errmsg' => '干线更新失败！'];
        }

        return ['errno' => 0, 'errmsg' => ''];
    }

    /**
     * 删除干线
     * @param $data['city_id'] interger Y 城市ID
     * @param $data['road_id'] string   Y 干线ID
     * @return array
     */
    public function delete($data)
    {
        if (empty($data)) {
            return ['errno' => 0, 'errmsg' => ''];
        }

        $where = 'road_id = "' . strip_tags(trim($data['road_id'])) . '"';
        $where .= ' and city_id = ' . intval($data['city_id']);
        $this->db->where($where);
        $updateData = [
            'is_delete'  => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->update($this->tb, $updateData);
        if ($this->db->affected_rows() < 1) {
            return ['errno' => -1, 'errmsg' => '干线更新失败！'];
        }

        return ['errno' => 0, 'errmsg' => ''];
    }

    /**
     * 查询干线详情
     * @param $data['city_id'] interger Y 城市ID
     * @param $data['road_id'] string   Y 干线ID
     * @return json
     */
    public function getRoadDetail($data)
    {
        if (empty($data)) {
            return (object)[];
        }

        $result = [];

        // 获取详情
        $where = 'city_id = ' . intval($data['city_id']);
        $where .= ' and road_id = "' . strip_tags(trim($data['road_id'])) . '"';
        $where .= ' and is_delete = 0';

        $this->db->select('logic_junction_ids');
        $this->db->from($this->tb);
        $this->db->where($where);

        $res = $this->db->get()->row_array();
        if (!$res || empty($res['logic_junction_ids'])) {
            return (object)[];
        }

        $result = $this->formatRoadDetailData($data['city_id'], $res['logic_junction_ids']);

        return $result;
    }

    public function getAllRoadDetail($params)
    {
        $result = $this->db->select('road_id, logic_junction_ids, road_name, road_direction')
            ->from($this->tb)
            ->where('city_id', $params['city_id'])
            ->where('is_delete', 0)
            ->get()->result_array();

        if(!$result)
            return [];
        
        $results = [];

        foreach ($result as $item) {

            if(!($tmp = $this->redis_model->getData('Road_' . $item['road_id'])))
            {
                $tmp = $this->formatRoadDetailData($params['city_id'], $item['logic_junction_ids']);
                $this->redis_model->setData(json_encode($tmp));
            }
            else
            {
                $tmp = json_decode($tmp, true);
            }

            $tmp['road'] = $item;
            $results[] = $tmp;
        }

        return $results;
    }

    public function comparison($params)
    {
        $methods = [
            'stop_time_cycle' => 'sum(stop_time_cycle) as stop_time_cycle',
            'stop_delay' => 'sum(stop_delay) as stop_delay',
            'speed' => 'avg(speed) as speed'
        ];

        if(!array_key_exists($params['quota_key'], $methods)) {
            return [];
        }

        $junctionList = $this->db->select('logic_junction_ids')
            ->from('road')
            ->where('city_id', $params['city_id'])
            ->where('road_id', $params['road_id'])
            ->where('is_delete', 0)
            ->get()->first_row();

        if(!$junctionList)
            return [];

        $junctionIds = explode(',', $junctionList->logic_junction_ids);

        // 最新路网版本
        $allMapVersions = $this->waymap_model->getAllMapVersion();
        $newMapVersion = max($allMapVersions);

        // 调用路网接口获取干线路口信息
        $res = $this->waymap_model->getConnectPath($params['city_id'], $newMapVersion, $junctionIds);

        $dataKey = $params['direction'] == 1 ? 'forward_path_flows' : 'backward_path_flows';

        if(!isset($res[$dataKey]))
            return [];

        $logic_flow_ids = array_map(function ($v) {
            return $v['logic_flow']['logic_flow_id'] ?? '';
        }, $res[$dataKey]);

        $baseDates = $this->dateRange($params['base_start_date'], $params['base_end_date']);
        $evaluateDates = $this->dateRange($params['evaluate_start_date'], $params['evaluate_end_date']);
        $hours = $this->hourRange('00:00', '23:30');

        $result = $this->db->select('date, hour, ' . $methods[$params['quota_key']])
            ->from('flow_duration_v6_' . $params['city_id'])
            ->where_in('date', array_merge($baseDates, $evaluateDates))
            ->where_in('hour', $hours)
            ->where_in('logic_junction_id', $junctionIds)
            ->where_in('logic_flow_id', $logic_flow_ids)
            ->group_by(['date', 'hour'])->get()->result_array();

        if(!$result || empty($result))
            return [];

        return Collection::make($result)->groupBy([function ($v) use ($baseDates) {
            return in_array($v['date'], $baseDates) ? 'base' : 'evaluate';
        }, 'date'], function ($v) use ($params) {
            return Collection::make(array_combine($this->hourRange('00:00', '23:30'), array_fill(0, 48, null)))
                ->merge(array_column($v, $params['quota_key'], 'hour'))
                ->walk(function (&$v, $k) { $v = [$k, $v]; })
                ->values()->get();
        })->get();
    }

    private function dateRange($start, $end)
    {
        return array_map(function ($v) {
            return date('Y-m-d', $v);
        }, range(strtotime($start), strtotime($end), 60 * 60 * 24));
    }

    private function hourRange($start, $end)
    {
        return array_map(function ($v) {
            return date('H:i', $v);
        }, range(strtotime($start), strtotime($end), 30 * 60));
    }

    /**
     * 格式化干线详情数据
     * @param $city_id interger 城市ID
     * @param $ids     string   路口ID串
     * @return array
     */
    private function formatRoadDetailData($cityId, $ids)
    {
        $result = [];

        $junctionIds = array_filter(explode(',', preg_replace("/(\n)|(\s)|(\t)|(\')|(')|(，)/" ,',' ,$ids)));

        // 最新路网版本
        $allMapVersions = $this->waymap_model->getAllMapVersion();
        $newMapVersion = max($allMapVersions);

        // 调用路网接口获取干线路口信息
        $res = $this->waymap_model->getConnectPath($cityId, $newMapVersion, $junctionIds);
        if (empty($res['junctions_info']) || empty($res['forward_path_flows']) || empty($res['backward_path_flows'])) {
            return (object)[];
        }

        foreach ($res['forward_path_flows'] as $k=>$v) {
            $result['road_info'][$k] = [
                'start_junc_id' => $v['start_junc_id'],
                'end_junc_id' => $v['end_junc_id'],
                'links' => $v['path_links'],
            ];
            // 正向geojson
            $geojson = $this->waymap_model->getLinksGeoInfos(explode(',', $v['path_links']), $cityId, $newMapVersion);
            $result['road_info'][$k]['forward_geo'] = $geojson;

            foreach ($res['backward_path_flows'] as $kk=>$vv) {
                if ($v['start_junc_id'] == $vv['end_junc_id']
                    && $v['end_junc_id'] == $vv['start_junc_id'])
                {
                    $result['road_info'][$k]['reverse_links'] = $vv['path_links'];
                    // 反向geojson
                    $geojson = $this->waymap_model->getLinksGeoInfos(explode(',', $vv['path_links']), $cityId, $newMapVersion);
                    $result['road_info'][$k]['reverse_geo'] = $geojson;
                }
            }
        }

        $countData = [
            'lng' => 0,
            'lat' => 0,
        ];
        foreach ($junctionIds as $v) {
            $result['junctions_info'][$v] = [
                'logic_junction_id' => $v,
                'junction_name'     => $res['junctions_info'][$v]['name'] ?? '未知路口',
                'lng'               => $res['junctions_info'][$v]['lng'] ?? 0,
                'lat'               => $res['junctions_info'][$v]['lat'] ?? 0,
                'node_ids'          => $res['junctions_info'][$v]['node_ids'] ?? [],
            ];
            $countData['lng'] += $res['junctions_info'][$v]['lng'] ?? 0;
            $countData['lat'] += $res['junctions_info'][$v]['lat'] ?? 0;
        }

        if (empty($result)) {
            return (object)[];
        }

        $result['center'] = [
            'lng' => count($result['junctions_info']) >= 1 ? $countData['lng'] / count($result['junctions_info']) : 0,
            'lat' => count($result['junctions_info']) >= 1 ? $countData['lat'] / count($result['junctions_info']) : 0,
        ];
        $result['junctions_info'] = array_values($result['junctions_info']);
        $result['map_version'] = $newMapVersion;

        return $result;
    }

    /**
     * 校验干线名称是否存在
     */
    private function isRoadNameExisted($name, $roadId = '')
    {
        $sqlArr = [$name];

        $sql = 'select road_id from ' . $this->tb;
        $sql .= ' where road_name = ?';
        if (!empty($roadId)) {
            $sql .= ' and road_id != ?';
            array_push($sqlArr, $roadId);
        }

        $res = $this->db->query($sql, $sqlArr)->result_array();

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 校验数据表是否存在
     */
    private function isTableExisted($table)
    {
        $isExisted = $this->db->table_exists($table);
        return $isExisted;
    }
}
