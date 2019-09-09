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
        $total = $this->opttask_model->TaskTotal($params['city_id'], $params['task_type'], $limit, $offset);
        $task_list = $this->opttask_model->TaskList($params['city_id'], $params['task_type'], $limit, $offset);

        $road_ids = array_column($task_list, 'road_id');
        $road_list = [];
        if (!empty($road_ids)) {
            $road_list = $this->road_model->getRoadsByRoadIDs($road_ids);
        }
        $road_id_name_map = [];
        foreach ($road_list as $value) {
            $road_id_name_map[$value['road_id']] = $value['road_name'];
        }

        $task_list = array_map(function($item) use($road_id_name_map) {
            $config = json_decode($item['config'], true);
            $weekday = $config['timing']['weekday'];
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
                'date_source' => $config['timing']['date_source'],
                'time_point' => implode(' ', $config['timing']['time_point']),
                'weekday' => implode(' ', $weekdays),
                'status'=> $status,
                'action' => $action,
            ];
        }, $task_list);
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
        $task_id = $params['task_id'];
        $city_id = $params['city_id'];
        $task_name = $params['task_name'];
        $task_type = $params['task_type'];
        $road_id = $params['road_id'];
        unset($params['task_id']);
        $start_date = $params['timing']['start_date'];
        $end_date = $params['timing']['end_date'];
        unset($params['city_id']);
        unset($params['task_name']);
        unset($params['task_type']);
        unset($params['road_id']);
        if ($task_id <= 0) {
            return $this->opttask_model->CreateTask($city_id, $task_name, $task_type, $road_id, $params, $start_date, $end_date);
        } else {
            return $this->opttask_model->UpdateTask($task_id, $city_id, $task_name, $task_type, $road_id, $params, $start_date, $end_date);
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
            $config = json_decode($data['config'], true);
            $config['task_id'] = intval($data['id']);
            $config['city_id'] = intval($data['city_id']);
            $config['task_name'] = $data['task_name'];
            $config['task_type'] = intval($data['task_type']);
            $config['road_id'] = $data['road_id'];
            // todo: 获取最新干线路口更新路口信息
            return $config;
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
        $status = $params['action'];
        if ($status == 0 or $status == 1) {
            $fields = [
                'status' => $status,
            ];
        } elseif ($status == 2) {
            $fields = [
                'is_deleted' => 1,
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

        $task_list = $this->opttask_model->TaskListByCityID($city_id, $task_type);
        $road_task_map = [];
        $new_task_list = [];
        foreach ($task_list as $value) {
            $road_task_map[$value['road_id']] = $value['id'];
            $new_task_list[$value['id']] = $value;
        }
        $task_list = $new_task_list;
        $road_ids = array_column($task_list, 'road_id');
        $road_ids[] = $road_id;

        $road_list = [];
        if (!empty($road_ids)) {
            $road_list = $this->road_model->getRoadsByRoadIDs($road_ids);
        }
        foreach ($road_list as $key => $value) {
            if ($value['road_id'] == $road_id) {
                $road_info = $value;
            }
        }
        $data = [];
        if (isset($road_info)) {
            $junction_list = explode(',', $road_info['logic_junction_ids']);
            foreach ($junction_list as $junction_id) {
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
                            'task_id' => $task_list[$road_task_map[$road['road_id']]]['id'],
                            'task_name' => $task_list[$road_task_map[$road['road_id']]]['task_name'],
                        ];
                    }
                }
                $data[] = $one;
            }
        }

        return $data;
    }

    /**
     * 创建、更新任务
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function SearchRoad($params) {
        $city_id = $params['city_id'];
        $keyword = $params['keyword'];
        $road_list =$this->road_model->searchRoadsByKeyword($city_id, $keyword);
        $road_list = array_map(function($item) {
            return [
                'road_id' => $item['road_id'],
                'road_name' => $item['road_name'],
            ];
        }, $road_list);
        return $road_list;
    }

    /**
     * 获取结果列表
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function ResultList($params) {
        $city_id = $params['city_id'];
        $task_id = $params['task_id'];
        $result_list =$this->opttaskresultroad_model->ResultList($city_id, $task_id);
        $result_list = array_map(function($item) {
            return [
                'result_id' => $item['id'],
                'created_at' => $item['created_at'],
            ];
        }, $result_list);
        return $result_list;
    }

    /**
     * 根据结果id获取任务配置详情
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function ResultTaskInfo($params) {
        $city_id = $params['city_id'];
        $result_id = $params['result_id'];
        $data = $this->opttaskresultroad_model->ResultTaskInfo($result_id);
        if (!empty($data)) {
            $data = $data[0];
            $config = json_decode($data['config'], true);
            $config['task_id'] = intval($data['task_id']);
            $config['city_id'] = intval($data['city_id']);
            $config['task_name'] = $data['task_name'];
            $config['task_type'] = intval($data['task_type']);
            $config['road_id'] = $data['road_id'];
            return $config;
        } else {
            return [];
        }
    }

    /**
     * 获取结果字段
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function GetResultField($params) {
        $result_id = $params['result_id'];
        $field = $params['field'];
        $result =$this->opttaskresultroad_model->GetField($result_id, $field);
        if (!empty($result)) {
            $data = json_decode($result[0], true);
            if (isset($data['data'])) {
                return $data['data'];
            }
        }
        return [];
    }
}