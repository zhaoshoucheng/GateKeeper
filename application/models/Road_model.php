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

    public function comparison($params)
    {
        $junctionList = $this->db->select('logic_junction_ids')
            ->from('road')
            ->where('city_id', $params['city_id'])
            ->where('road_id', $params['road_id'])
            ->where('is_delete', 0)
            ->get()->first_row();

        if(!$junctionList) {
            return [];
        }

        $junctionIds = $junctionList['logic_junction_ids'];

        $roadInfo = $this->formatRoadDetailData($params['city_id'], $junctionIds);


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
        $where = 'road_name = "' . $name . '"';
        if (!empty($roadId)) {
            $where .= ' and road_id != "' . $roadId . '"';
        }

        $this->db->select('road_id');
        $this->db->from($this->tb);
        $this->db->where($where);
        $res = $this->db->get()->result_array();

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
