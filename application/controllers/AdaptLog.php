<?php
/**
 * 日志记录类
 */
use \Services\AdaptionLogService;
defined('BASEPATH') OR exit('No direct script access allowed');

class AdaptLog extends MY_Controller{
    protected $adaptionLogService;
    public function __construct()
    {
        parent::__construct();
        $this->load->config('nconf');
        $this->load->helper('http');
        $this->adaptionLogService = new AdaptionLogService();
    }

    private function access(){
        if(gethostname()=="ipd-cloud-preweb00.gz01" || gethostname()=="ipd-cloud-server01.gz01"){
            return true;
        }
        return false;
    }

    public function rollback(){
        if(!$this->access()){
            echo "access deny";exit;
        }
        $params = $this->input->get();
        if(empty($params["trace_id"])){
            echo "trace_id empty";
            exit;
        }
        if(empty($params["junction_id"])){
            echo "junction_id empty";
            exit;
        }
        $qurl = $this->config->item('signal_light_interface')."/profile/rollback?junctionId=".$params["junction_id"];
        $ret = httpGET($qurl,[]);
        $message =  "rollback url=".$qurl."||client_ip=".$_SERVER["REMOTE_ADDR"]."||status=".json_encode($ret);
        echo $message." <a href='javascript:history.back(-1);'>返回</a>";

        $params = [
            "type"=>1,
            "rel_id"=>$params["junction_id"],
            "log"=>$message,
            "trace_id"=>$params["trace_id"],
            "dltag"=>"_didi_Junction.manual.rollback",
            "log_time"=>date("Y-m-d H:i:s"),
        ];
        $this->adaptionLogService->insert($params);
        exit;
    }


    public function index()
    {
        if(!$this->access()){
            echo "access deny";exit;
        }
        $params = $this->input->get();
        $params["page_size"] = $params["page_size"]??100;
        $params["trace_id"] = $params["trace_id"]??"";
        $params["type"] = 0;
        list($totalRow,$rowList)=$this->adaptionLogService->pageList($params);
        $this->load->library('pagination');
        $config['base_url'] = '/signalpro/api/AdaptLog/index';
        $config['attributes'] = array('class' => 'myclass');
        $config['suffix'] = "&trace_id=".$params["trace_id"];
        $config['total_rows'] = $totalRow;
        $config['per_page'] = $params["page_size"];   //每页条数
        $config['page_query_string'] = TRUE;
        $this->pagination->initialize($config);

        $data = [];
        $data["page"] = $this->pagination->create_links();
        $data["list"] = $rowList;
        $this->load->view('adaptlog/index',$data);
        exit;
    }

    public function junction()
    {
        if(!$this->access()){
            echo "access deny";exit;
        }
        if(!$this->access()){
            echo "access deny";exit;
        }
        $params = $this->input->get();
        $params["page_size"] = $params["page_size"]??100;
        $params["trace_id"] = $params["trace_id"]??"";
        $params["dltag"] = $params["dltag"]??"";
        $params["rel_id"] = $params["rel_id"]??"";

        $params["type"] = 1;
        list($totalRow,$rowList)=$this->adaptionLogService->pageList($params);
        $this->load->library('pagination');
        $config['base_url'] = '/signalpro/api/AdaptLog/junction';
        $config['attributes'] = array('class' => 'myclass');
        $config['suffix'] = "&trace_id=".$params["trace_id"]."&dltag=".$params["dltag"]."&rel_id=".$params["rel_id"];
        $config['total_rows'] = $totalRow;
        $config['per_page'] = $params["page_size"];   //每页条数
        $config['page_query_string'] = TRUE;
        $this->pagination->initialize($config);

        $data = [];
        $data["page"] = $this->pagination->create_links();
        $data["list"] = $rowList;
        $data["rel_id"] = $params["rel_id"];
        $data["trace_id"] = $params["trace_id"];
        $this->load->view('adaptlog/junction',$data);
        exit;
    }

    public function insert()
    {
        $params = $this->input->post();
        $this->validate([
            'type' => 'required|min_length[1]',
            'rel_id' => 'required|min_length[1]',
            'log' => 'required|min_length[1]',
            'trace_id' => 'required|min_length[1]',
            'dltag' => 'required|min_length[1]',
            'log_time' => 'required|min_length[1]',
        ]);
        $ret=["errno"=>0,"errmsg"=>"",];
        echo json_encode($ret);
        fastcgi_finish_request();
        $this->adaptionLogService->insert($params);
    }
}