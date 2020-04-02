<?php
/**
 * sts_index数据库,查询pi使用
 * User: didi
 * Date: 2019/10/28
 * Time: 上午11:47
 */

use Services\DataService;

class Pi_model extends CI_Model{

    private $tb = 'junction_duration_v6_';

    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('sts_index', true);
        $this->dataService = new DataService();
    }

    public function getJunctionsPi($dates,$junctionIDs,$cityId,$hours){

        $res = $this->db
            ->from($this->tb.$cityId)
            ->where_in('date', $dates)
            ->where_in('hour',$hours)
            ->where_in('logic_junction_id', $junctionIDs)
            ->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    public function getGroupJuncAvgPiWithDates($cityID,$logic_junction_ids,$dates,$hours){

            $req = [
                'city_id' => (int)$cityID,
                'logic_junction_ids' => $logic_junction_ids,
                "dates" => $dates,
                'hours'=>$hours
            ];

            $url = $this->config->item('data_service_interface');


            $res = httpPOST($url . '/report/GetPiByJunction', $req, 0, 'json');
            $res = json_decode($res,true);
            if(empty($res)){
                return [];
            }
//            var_dump($req);
            $trajSum = 0;
            $piSum = 0;
            foreach ($res['data'] as $v){
                $trajSum += $v['traj_count'];
                $piSum += $v['pi']*$v['traj_count'];
            }
            if($trajSum>0){
                return $piSum/$trajSum;
            }
            return 0;


    }

    public function getGroupJuncPiWithDatesHours($cityID,$logic_junction_ids,$dates,$hours){
        //南京,济南pi数据迁入es


            $req = [
                'city_id' => (int)$cityID,
                'logic_junction_ids' => $logic_junction_ids,
                "dates" => $dates,
//                'start_date' => (int)$st,
//                'end_date' => (int)$et,
                'hours'=>$hours
            ];

            $url = $this->config->item('data_service_interface');


            $res = httpPOST($url . '/report/GetPiByJunction', $req, 0, 'json');
            $res = json_decode($res,true);
            $hourPI = [];
            if(empty($res)){
                return [];
            }
            foreach ($res['data'] as $v){
                $hourPI[$v['hour']] = $v['pi'];
            }
            return $hourPI;



    }

    public function getJunctionsPiWithDatesHours($city_id, $logic_junction_ids, $dates, $hours){
      
//        if ($city_id == 11 || $city_id == 12 ) {
            $pi_data = $this->dataService->call("/report/GetPiIndex", [
                'city_id' => $city_id,
                'dates' => $dates,
                'logic_junction_ids' => $logic_junction_ids,
                'hours' => $hours,
                'group_by' => 'logic_junction_id',
            ], "POST", 'json');
            $data = [];
            foreach ($pi_data[2] as $value) {
                $data[] = [
                    'logic_junction_id' => $value['key'],
                    'pi' => $value['traj_count']['value'] == 0 ? 0 : $value['pi']['value'] / $value['traj_count']['value'],
                ];
            }
            return $data;
//        } else {
//            $query = $this->db
//                ->select('logic_junction_id, sum(pi * traj_count) / sum(traj_count) as pi')
//                ->from($this->tb.$city_id)
//                ->where_in('date', $dates)
//                ->where_in('hour', $hours);
//            if(!empty($logic_junction_ids)){
//                $query = $query->where_in('logic_junction_id', $logic_junction_ids);
//            }
//            $res = $query->group_by('logic_junction_id')->get();
//            if(empty($res)){
//                return [];
//            }
//    //         var_dump($this->db->last_query());
//            return $res->result_array();
//        }

    }

    public function getJunctionsPiByHours($city_id, $logic_junction_ids, $dates){
        if ($city_id == 11|| $city_id == 12) {
            $pi_data = $this->dataService->call("/report/GetPiIndex", [
                'city_id' => $city_id,
                'dates' => $dates,
                'logic_junction_ids' => $logic_junction_ids,
                'group_by' => 'hour',
            ], "POST", 'json');
            $data = [];
            foreach ($pi_data[2] as $value) {
                $data[] = [
                    'hour' => $value['key'],
                    'pi' => $value['traj_count']['value'] == 0 ? 0 : $value['pi']['value'] / $value['traj_count']['value'],
                ];
            }
            return $data;
        } else {
            $res = $this->db
                ->select('hour, sum(pi * traj_count) / sum(traj_count) as pi')
                ->from($this->tb.$city_id)
                ->where_in('logic_junction_id', $logic_junction_ids)
                ->where_in('date', $dates)
                ->group_by('hour')
                ->get();
            if(empty($res)){
                return [];
            }
    //         var_dump($this->db->last_query());
            return $res->result_array();
        }
    }
}