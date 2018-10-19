<?php

/*
 * 济南大脑报警接口
 */

class Warning extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('nconf');
        $this->load->helper('http');
    }

    public function notify()
    {
        $params = $this->input->post();
        $params = array_merge($params, $this->input->get());

        $validate = Validate::make($params, [
            'send_type' => 'min:1',
            'tos' => 'min:1',
            'subject' => 'min:1',
            'content' => 'min:1',
            'log_id' => 'min:0',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }
        try {
            $data = [
                'app' => "std",
                'subject' => $params["subject"],
                'tos' => explode(",",$params["tos"]),
                'content' => ["msg" => $params["content"]],
            ];
            $ret = httpPOST($this->config->item('warning_interface') . '/api/v2/notify/mail?sys=Itstool', $data, 0, "json");
            $ret = json_decode($ret, true);
            if (isset($ret['code']) && $ret['code'] != 0) {
                $message = isset($ret["msg"]) ? $ret["msg"] : "";
                com_log_warning('warning_interface_error', 0, $message, compact("data","ret"));
                $this->errno = -1;
                $this->errmsg = 'warning_interface_error';
                return;
            }
            $this->output_data = $ret['data'];
        } catch (Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
        return $this->response("");
    }
}