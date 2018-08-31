<?php

/**
 * 周报模块
 */
class Report_model extends CI_Model
{
    private $tb = 'report';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $is_existed = $this->db->table_exists($this->tb);
        if (!$is_existed) {
            return [];
        }
        $this->load->model('waymap_model');
        $this->load->model('gift_model');
        $this->load->config('nconf');
    }

    public function test()
    {
        return array(
            array(
                'start_time' => "12:00:00",
                'end_time' => "13:00:00",
                'logic_junction_id' => "123456",
                'stop_delay' => "2.3333",
            ), array(
                'start_time' => "13:00:00",
                'end_time' => "14:00:00",
                'logic_junction_id' => "1234567",
                'stop_delay' => "1.2222",
            )
        );
    }

    public function generate($cityId, $title, $type)
    {
        //上传图片
        $data = $this->gift_model->Upload("file");

        $retryNum = 0;
        while (1){
            if($retryNum>1000){
                throw new \Exception("生成的报告太多.");
            }
            $this->db->where('city_id', $cityId);
            $this->db->where('type', $type);
            $this->db->where('title', $title);
            $this->db->where('create_at >=', date("Y-m-d 00:00:00"));
            $this->db->from('report');
            $num = $this->db->count_all_results();
            echo $this->db->last_query();die;
            if($num==0){
                break;
            }
            $title = str_replace("(".$retryNum.")", '', $title)."(".($retryNum+1).")";
            $retryNum++;
        }

        //插入
        $param = [
            "city_id" => $cityId,
            "title" => $title,
            "type" => $type,
            "update_at" => date("Y-m-d H:i:s"),
            "create_at" => date("Y-m-d H:i:s"),
        ];
        $this->db->insert("report", $param);
        $itemId = $this->db->insert_id();

        //插入新记录
        foreach ($data as $namespace => $item) {
            $param = [
                "file_key" => $item['resource_key'],
                "item_id" => $itemId,
                "namespace" => $namespace,
                "b_type" => 1,
                "update_at" => date("Y-m-d H:i:s"),
                "create_at" => date("Y-m-d H:i:s"),
            ];
            $this->db->insert('upload_files', $param);
        }
        return $data['itstool_private'];
    }

    private function _reportListscope($cityId, $type = 1, $pageNum = 1, $pageSize = 100, $namespace){
        $this->db->from('report');
        $this->db->join('upload_files', 'upload_files.item_id = report.id');
        $this->db->where('report.delete_at', "1970-01-01 00:00:00");
        $this->db->where('upload_files.delete_at', "1970-01-01 00:00:00");
        $this->db->where('upload_files.namespace', $namespace);
        $this->db->where('report.city_id', $cityId);
        $this->db->where('report.type', $type);
    }

    public function getReportList($cityId, $type = 1, $pageNum = 1, $pageSize = 100)
    {
        $namespace = 'itstool_private';

        $this->_reportListscope($cityId, $type, $pageNum, $pageSize, $namespace);
        $this->db->select('count(*) as num');
        $statRow = $this->db->get()->row_array();

        $this->_reportListscope($cityId, $type, $pageNum, $pageSize, $namespace);
        $this->db->select('report.id,report.title,report.create_at,file_key,namespace');
        $this->db->order_by('report.id', 'DESC');
        $this->db->limit($pageSize, ($pageNum - 1) * $pageSize);
        $query = $this->db->get();
        $result = $query->result_array();


        $formatResult = function ($result) use ($statRow,$namespace,$pageNum,$pageSize) {
            $resourceKeys = array_reduce($result, function ($carry, $item) {
                if (!empty($item["file_key"])) {
                    $carry[] = $item["file_key"];
                }
                return $carry;
            }, []);

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $lastPos = strrpos($currentUrl, '/');
            $baseUrl = substr($currentUrl, 0, $lastPos);
            foreach ($result as $key => $item) {
                $itemInfo = $this->gift_model->getResourceUrlList($resourceKeys, $namespace);
                if (!empty($itemInfo[$item["file_key"]])) {
                    $result[$key]['url'] = $itemInfo[$item["file_key"]]['download_url'];
                    $result[$key]['down_url'] = $baseUrl . "/downReport?key=" . $item["file_key"];
                }
            }
            return [
                "list"=>$result,
                "total"=>$statRow['num'],
                "page_no"=>$pageNum,
                "page_size"=>$pageSize,
            ];
        };
        return $formatResult($result);
    }
}