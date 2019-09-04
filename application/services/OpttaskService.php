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
        $data = $this->opttask_model->TaskList($params['city_id'], $params['task_type'], $limit, $offset);
        $road_ids = array_column($data, 'road_id');
        // $road_infos =
        $task_list = array_map(function($item) {
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
                'road_id' => $item['road_id'],
                // 'road_name' => $item['task_name'],
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
        $task_id = $params['task_id'];
        $city_id = $params['city_id'];
        $task_name = $params['task_name'];
        $task_type = $params['task_type'];
        $road_id = $params['road_id'];
        $config = $params['config'];
        if ($task_id <= 0) {
            return $this->opttask_model->CreateTask($city_id, $task_name, $task_type, $road_id, $config);
        } else {
            $config['task_id'] = $task_id;
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
            return $data[0];
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
}