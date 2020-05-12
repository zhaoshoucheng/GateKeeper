<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2019/12/27
 * Time: 下午3:19
 */


defined('BASEPATH') OR exit('No direct script access allowed');

use Services\SpreadsheetService;


class DownloadSpreadsheet extends MY_Controller{
    public function __construct()
    {
        parent::__construct();
        $this->SpreadsheetService = new SpreadsheetService();
    }

    /*
     * file 图片
     * value:{
     *  "logic_junction_id":"",
     *  "junction_name":"",
     *  "opt_rets":[
     *      {
     *          "name":""
     *          "start_time":"",
     *          "end_time":""
     *      }
     *  ]
     * }
     * */
    public function SingleTimeOpt(){
        $params = $this->input->post(null, true);

        $obj = json_decode($params['value'],true);

        $this->SpreadsheetService->checkUploadFile($_FILES);

        $this->SpreadsheetService->SingleTimeOptSpreadsheet($_FILES,$obj);

    }

    public function AreaTimeOpt(){
        $this->convertJsonToPost();
        $params = $this->input->post(null, true);
        $this->validate([
            'values' => 'required|trim',
        ]);
        $obj = $params['values'];
        $this->SpreadsheetService->AreaTimeOptSpreadsheet($obj);
    }

    /*
    * file 图片
    * value:{
    *  "logic_junction_id":"",
    *  "junction_name":"",
    *  "time":"",
     * "cycle":100,
     * "offset":10,
    *  "opt_rets":[
    *      {
    *          "name":""
    *          "start_time":"",
    *          "end_time":"",
     *         "yellow":"",
     *         "green_length":1
    *      }
    *  ]
    * }
    * */
    public function SingleGreenOpt(){
        $params = $this->input->post(null, true);
        $obj = json_decode($params['value'],true);

        $this->SpreadsheetService->checkUploadFile($_FILES);

        $this->SpreadsheetService->SingleGreenOptSpreadsheet($_FILES,$obj);
    }

    /*
       * file 图片
       * value:{
       *  "road_id":"",
       *  "road_name":"",
       *  "opt_rets":[
       *      {
       *          "direction":"",
       *          "junction_info":{
     *                "name":"aa",
       *              "cycle":1,
       *              "offset":1,
       *              "start_time":1,
       *              "green_length":1,
       *          }
       *      }
       *  ]
       * }
   * */
    public function RoadTrafficOpt(){
        $params = $this->input->post(null, true);
        $obj = json_decode($params['value'],true);

        $this->SpreadsheetService->checkUploadFile($_FILES);

        $this->SpreadsheetService->RoadTrafficOptSpreadsheet($_FILES,$obj);
    }



}