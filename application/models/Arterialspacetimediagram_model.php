<?php
/********************************************
# desc:    干线时空图数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-06-29
********************************************/
use Didi\Cloud\ItsMap\Arterialspacetimediagram_vendor;

class Arterialspacetimediagram_model extends CI_Model
{
    private $tb = '';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('taskdateversion_model');
    }

    /**
    * 获取干线时空图
    * @param $data['junctions']  array Y 路口信息集合 如下：
    *   $data['junctions'] = [
    *       [
    *           'junction_id'     => '2017030116_5138189', // 路口ID
    *           'forward_flow_id' => '2017030116_i_73821231_2017030116_o_877944131', // 正向flow
    *           'reverse_flow_id' => '2017030116_i_877944150_2017030116_o_877944100', // 反向flow
    *           'tod_start_time'  => '16:00:00', // 配时方案开始时间
    *           'tod_end_time'    => '19:30:00', // 配时方案结束时间
    *           'cycle'           => 220         // 配时周期
    *       ],
    *   ]
    * @param $data['task_id']    interger Y 任务ID
    * @param $data['dates']      array    Y 评估/诊断日期
    * @param $data['time_point'] string   Y 查询时间点
    * @param $data['method']     interger Y 0=>正向 1=>反向 2=>双向
    * @param $data['token']      string   Y 此次请求唯一标识，用于前端轮询
    * @return array
    */
    public function getSpaceTimeDiagram($data)
    {
    	// 获取map_version
        $mapversions = $this->taskdateversion_model->select($data['task_id'], $data['dates']);
        if (!$mapversions) {
            return [];
        }

        // 组织thrift所需version数组
        foreach ($mapversions as $k=>$v) {
            $version[$k]['map_version'] = $v['map_version_md5'];
            $version[$k]['date'] = $v['date'];
        }

        $data['version'] = $version;

    	$server = new Arterialspacetimediagram_vendor();
    	$res = $server->getSpaceTimeDiagram($data);
        if (!$res) {
            return [];
        }

        $res = (array)$res;
        if ($res['errno'] != 0 || empty($res['junction_mes'])) {
            return [];
        }

        $result = [];
        foreach ($res['junction_mes'] as $k=>$v) {
            $v = (array)$v;
            $result['dataList'][$k]['junction_id'] = $v['junction_id'] ?? '';
            $result['dataList'][$k]['forward_speed'] = isset($v['forward_speed']) ? round($v['forward_speed'], 2) : 0;
            $result['dataList'][$k]['reverse_speed'] = isset($v['reverse_speed']) ? round($v['reverse_speed'], 2) : 0;
            // 正向
            if (!empty($v['forward_traj'])) {
                foreach ($v['forward_traj'] as $kk=>$vv) {
                    foreach ($vv as $kkk=>$vvv) {
                        $vvv = (array)$vvv;
                        // 时间
                        $result['dataList'][$k]['forward_traj'][$kkk][0] = $vvv['timestamp'];
                        // 值
                        $result['dataList'][$k]['forward_traj'][$kkk][1] = $vvv['distance'] * -1;
                    }
                }
            }

            // 反向
            if (!empty($v['reverse_traj'])) {
                foreach ($v['reverse_traj'] as $kk=>$vv) {
                    foreach ($vv as $kkk=>$vvv) {
                        $vvv = (array)$vvv;
                        // 时间
                        $result['dataList'][$k]['reverse_traj'][$kkk][0] = $vvv['timestamp'];
                        // 值
                        $result['dataList'][$k]['reverse_traj'][$kkk][1] = $vvv['distance'];
                    }
                }
            }
        }

        return $result;
    }
}