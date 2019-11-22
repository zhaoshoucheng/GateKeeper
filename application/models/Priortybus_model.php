<?php
/********************************************
 * # desc:    公交优先模型
 ********************************************/

/**
 * Class Priortybus_model
 */
class Priortybus_model extends CI_Model
{
    /**
     * PriortyBus_model constructor.
     *
     * @throws \Exception 
     */
    public function __construct()
    { 
        parent::__construct();
        $this->load->config('nconf');
        $this->load->config('realtime_conf');
        $this->load->helper('http');
    }

    public function getStationJuncInfo($roadID)
    {
        $params = [ 
            'road_id'=>$roadID,
        ];
        $businfoInterface = $this->config->item('businfo_interface');
        $url = $businfoInterface . '/priorityBus/stationjuncInfo';
        $resBody = httpGET($url,$params);
        $res = json_decode($resBody, true);
        return $res["data"]??[];
    }

    public function getStationJuncInfoMock($roadID)
    {
        $resBody = '{
          "errno": 0,
          "errmsg": "",
          "data": {
            "station": [
              {
                "station_id": "11",
                "station_name": "站点名1",
                "lng": "118.72493",
                "lat": "31.99388"
              },
              {
                "station_id": "22",
                "station_name": "站点名2",
                "lng": "118.72493",
                "lat": "31.99388"
              }
            ],
            "junctions_info": [
              {
                "logic_junction_id": "2017030116_3873289",
                "is_priority": "1"
              }
            ]
          },
          "traceid": "ce0c6c8df9de4233bf62fb97c6aed804",
          "username": "unkown",
          "time": {
            "a": "0.1227秒",
            "s": "0.1197秒"
          }
        }';
        $res = json_decode($resBody, true);
        return $res["data"]??[];
    }

}
