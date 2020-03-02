<?php

class Wordreport_model extends CI_Model{

    private $_table = 'word_report';

    public function __construct(){
        parent::__construct();
        $this->db = $this->load->database('default', true);
        $this->load->config('nconf');
        $this->chartimg_interface = $this->config->item('chart_img');
    }

    /*
     * 保存数据入库
     * city_id 城市id
     * title word标题
     * type 报告类型
     * time_range报告分析日期
     * file_path 文件保存路径
     * task_id 任务id
     * status 任务状态
     * user_info 提交用户
     * */
    public function updateWordReport($taskID,$filePath,$status){
        return $this->db->where('task_id', $taskID)
            ->update($this->_table, array(
                'status'=>$status,
                'file_path'=>$filePath
            ));
    }

    /*
     * 新建生成报告任务
     * */
    public function createWordReport($data){
        $ret = $this->db->insert($this->_table,$data);
        return $ret;
    }

    public function queryWordReport($taskID){
        $ret = $this->db->from($this->_table)->where('task_id',$taskID)->get()->result_array();
        return $ret;
    }

    public function queryWordReportByID($id){
        $ret = $this->db->from($this->_table)->where('id',$id)->get()->result_array();
        return $ret;
    }

    /**
     * @param $cityId
     * @param $type
     * @param $pageNum
     * @param $pageSize
     *
     * @return array
     */
    public function getCountUploadFile($cityId, $type, $pageNum, $pageSize)
    {
        $res = $this->db->select('count(*) as num')
            ->from('word_report')
            ->where('deleted_at', "1970-01-01 00:00:00")
            ->where('city_id', $cityId)
            ->where('type', $type)
            ->where('status', 1)
            ->get();
        return $res instanceof CI_DB_result ? $res->row_array() : $res;
    }

    /**
     * @param $cityId
     * @param $type
     * @param $pageNum
     * @param $pageSize
     * @param $namespace
     *
     * @return array
     */
    public function getSelectUploadFile($cityId, $type, $pageNum, $pageSize)
    {
        $res = $this->db->select('id, title, create_at, time_range')
            ->from('word_report')
            ->where('deleted_at', "1970-01-01 00:00:00")
            ->where('city_id', $cityId)
            ->where('type', $type)
            ->where('status', 1)
            ->order_by('id', 'DESC')
            ->limit($pageSize, ($pageNum - 1) * $pageSize)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }
    //生成chart图片
    public function generateChartImg($jsonStr){
//        $temp = tmpfile();
        $temp_file = tempnam(sys_get_temp_dir(), 'chart');

//        $jsonStr = json_encode($data);
//        $jsonStr = "{\"infile\":{\"title\": {\"text\": \"Steep Chart\"}, \"xAxis\": {\"categories\": [\"Jan\", \"Feb\", \"Mar\"]}, \"series\": [{\"data\": [29.9, 71.5, 106.4]}]}}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $this->chartimg_interface);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);

        curl_close($ch);
        $fp2 = @fopen($temp_file, "a");
        fwrite($fp2, $response);//向当前目录写入图片文件，并重新命名
        fclose($fp2);
        return $temp_file;

    }
}