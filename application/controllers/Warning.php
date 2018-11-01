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

    public function heartbeat()
    {
        $data = [
            [
                'name' => "LOG.signal_heartbeat",
                'timestamp' => time(),
                'value' => 1,
                'tags' => [
                    "host" => "ipd-cloud-web00.gz01",
                ],
                'step' => 10,
            ]
        ];
        try {
            $ret = httpPOST('http://collect.odin.xiaojukeji.com/api/v1/collector/push?ns=collect.hna.web.its-tool.ipd-cloud.didi.com', $data, 0, "json");
            $ret = json_decode($ret, true);
            if (isset($ret['code']) && $ret['code'] != 0) {
                $message = isset($ret["msg"]) ? $ret["msg"] : "";
                com_log_warning('warning_heartbeat_error', 0, $message, compact("data", "ret"));
                $this->errno = -1;
                $this->errmsg = 'warning_heartbeat_error';
                return;
            }
            $this->output_data = $ret['data'];
        } catch (Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
        return $this->response("");
    }

    public function notify()
    {
        $params = $this->input->post();
        $params = array_merge($params, $this->input->get());
        $warningUrl = 'http://monitor.odin.xiaojukeji.com';

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
            if($params["send_type"]==1){
                $data = [
                    'app' => "std",
                    'subject' => $params["subject"],
                    'tos' => explode(",",$params["tos"]),
                    'content' => ["msg" => $params["content"]],
                ];
                $ret = httpPOST($warningUrl . '/api/v2/notify/mail?sys=Itstool', $data, 0, "json");
            }else{
                $data = [
                    'app' => "std",
                    'tos' => explode(",",$params["tos"]),
                    'content' => ["msg" => $params["content"]],
                ];
                $ret = httpPOST($warningUrl . '/api/v2/notify/sms?sys=Itstool', $data, 0, "json");
            }
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