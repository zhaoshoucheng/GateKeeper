<?php
/***************************************************************
 * # 区域管理
 * # user:niuyufu@didichuxing.com
 * # date:2019-08-23
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\AlgorithmService;
class Algorithm extends MY_Controller
{
    protected $algorithmService;

    /**
     * Area constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->algorithmService = new AlgorithmService();
    }
    
    /**
     * 添加区域
     *
     * @throws Exception
     */
    public function getAllSelector()
    {
        $params = $this->input->post(null, true);
        // $this->validate([
        // ]);
        $data = $this->algorithmService->getAllSelector($params);
        $this->response($data);
    }
}
