<?php
/**
 * 信控平台 - 区域相关接口
 *
 * User: lichaoxi_i@didichuxing.com
 */

namespace Services;

// use Didi\Cloud\Collection\Collection;

/**
 * Class OpttaskService
 * @package Services
 * @property \Area_model $area_model
 */
class OpttaskService extends BaseService {
    protected $roadService;
    /**
     * OpttaskService constructor.
     * @throws \Exception
     */
    public function __construct() {
        parent::__construct();

        // $this->load->model('waymap_model');
        // $this->load->model('redis_model');
        $this->load->model('road_model');
        $this->load->model('opttask_model');
        $this->load->model('opttaskresultroad_model');

        $this->load->config('nconf');

        $this->roadService = new RoadService();
    }

    /**
     * 获取任务列表
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function TaskList($params) {
        $limit = $params['page_size'];
        $offset = $params['page_size'] * ($params['page'] - 1);
        $tatal = $this->opttask_model->TasTotal($params['city_id'], $params['task_type'], $limit, $offset);
        $task_list = $this->opttask_model->TaskList($params['city_id'], $params['task_type'], $limit, $offset);

        $road_ids = array_column($task_list, 'road_id');
        $road_list = $this->road_model->getRoadsByRoadID($road_ids);
        $road_id_name_map = [];
        foreach ($road_list as $value) {
            $road_id_name_map[$value['road_id']] = $value['road_name'];
        }

        $task_list = array_map(function($item) use($road_id_name_map) {
            $config = json_decode($item['config']);
            $weekday = $config['timing']['weekdays'];
            $weekdays = [];
            foreach ($weekday as $value) {
                if ($value == 1) {
                    $weekdays[] = '星期一';
                } elseif ($value == 1) {
                    $weekdays[] = '星期二';
                } elseif ($value == 3) {
                    $weekdays[] = '星期三';
                } elseif ($value == 4) {
                    $weekdays[] = '星期四';
                } elseif ($value == 5) {
                    $weekdays[] = '星期五';
                } elseif ($value == 6) {
                    $weekdays[] = '星期六';
                } elseif ($value == 7) {
                    $weekdays[] = '星期日';
                }
            }
            if ($item['status'] == 0) {
                // 运行中
                $status = 0;
                // 暂停
                $action = 1;
            } elseif ($item['status'] == 1) {
                // 未开始
                $status = 1;
                // 开始
                $action = 0;
            }
            if (strtotime($config['timing']['end_date']) < time()) {
                $status = 2;
            }
            return [
                'task_id' => $item['id'],
                'task_name' => $item['task_name'],
                'task_type' => $item['task_type'],
                'road_id' => $item['road_id'],
                'road_name' => $road_id_name_map[$item['road_id']],
                'direction' => $config['plan']['direction'],
                'timing_type' => $config['plan']['timing_type'],
                'opt_type' => $config['plan']['opt_type'],
                'equal_cycle' => $config['plan']['equal_cycle'],
                'time_point' => implode(' ', $config['timing']['time_point']),
                'weekday' => implode(' ', $weekdays),
                'status'=> $status,
                'action' => $action,
            ];
        }, $data);
        return [
            'page' => $params['page'],
            'page_size' => $params['page_size'],
            'total' => $total,
            'task_list' => $task_list,
        ];
    }

    /**
     * 创建、更新任务
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function UpdateTask($params) {
        $city_id = $params['city_id'];
        $task_id = $params['task_id'];
        $task_name = $params['task_name'];
        $task_type = $params['task_type'];
        $road_id = $params['road_id'];
        $config = $params['config'];
        $config['task_id'] = $task_id;
        $config['task_name'] = $task_name;
        $config['task_type'] = $task_type;
        $config['road_id'] = $road_id;
        if ($task_id <= 0) {
            return $this->opttask_model->CreateTask($city_id, $task_name, $task_type, $road_id, $config);
        } else {
            return $this->opttask_model->UpdateTask($task_id, $city_id, $task_name, $task_type, $road_id, $config);
        }
    }

    /**
     * 任务详情
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function TaskInfo($params) {
        $task_id = $params['task_id'];
        $data = $this->opttask_model->TaskInfo($task_id);
        if (!empty($data)) {
            $data = $data[0];
            $data['config']['task_id'] = $data['id'];
            return $data;
        } else {
            return [];
        }
    }

    /**
     * 修改任务状态
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function UpdateTaskStatus($params) {
        $task_id = $params['task_id'];
        $status = $params['status'];
        if ($status == 0 or $status == 1) {
            $fields = [
                'status' => $status,
            ];
        } elseif ($status == 2) {
            $fields = [
                'update_at' => time(),
            ];
        } else {
            return false;
        }

        return $data = $this->opttask_model->UpdateTaskStatus($task_id, $fields);
    }

    /**
     * 查询干线交叉信息
     *
     * @throws Exception
     */
    public function RoadConflict($params) {
        $city_id = $params['city_id'];
        $road_id = $params['road_id'];
        $task_type = $params['task_type'];

        $task_list = $this->Opttask->TaskListByCityID($city_id, $task_type);
        $road_task_map = [];
        $new_task_list = [];
        foreach ($task_list as $value) {
            $road_task_map[$value['road_id']] = $value['id'];
            $new_task_list[$value['id']] = $value;
        }
        $task_list = $new_task_list;
        $road_ids = array_column($task_list, 'road_id');
        $road_ids[] = $road_id;

        $road_list = $this->road_model->getRoadsByRoadID($road_ids);
        foreach ($road_list as $key => $value) {
            if ($value['road_id'] == $road_id) {
                $road_info = $value;
                unset($road_list[$key]);
            }
        }
        $data = [];
        if (isset($road_info)) {
            $junction_list = explode(',', $road_info['logic_junction_ids']);
            foreach ($junction_id as $junction_list) {
                $one = [
                    'logic_flow_id' => $junction_id,
                    'conflict_task' => [],
                ];
                foreach ($road_list as $road) {
                    if (!isset($road_task_map[$road['road_id']])) {
                        continue;
                    }
                    $tmp_junction_list = explode(',', $road['logic_junction_ids']);
                    if (in_array($junction_id, $tmp_junction_list)) {
                        $one['conflict_task'][] = [
                            'task_id' => $task_list[$road_task_map[$road['road_id']]]['task_id'],
                            'task_name' => $task_list[$road_task_map[$road['road_id']]]['task_name'],
                        ];
                    }
                }
            }
        }

        $this->response($data);
    }
}