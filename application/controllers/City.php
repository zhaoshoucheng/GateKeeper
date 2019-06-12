<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Overtrue\Pinyin\Pinyin;
class City extends MY_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->opencity_model = $this->load->model("opencity_model");
    }

    public function list()
    {
        // 获取citylist,然后获取拼音
        $cityInfos = $this->opencity_model->getCityInfos();
        if (empty($cityInfos)) {
            $this->errno = ERR_DATABASE;
            $this->errmsg = "获取开城城市列表失败";
            return; 
        }
        $permCitys = !empty($this->userPerm['city_id']) ? $this->userPerm['city_id'] : [];
        $data = [];
        $pinyinService = new Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
        foreach ($cityInfos as $info) {
            $cityId = $info['city_id'];
            if (!empty($permCitys) && !in_array($cityId, $permCitys)) {
                continue;
            }
            $center = $info['center_point'];
            $point = explode(",", $center);
            $name = $info['city_name'];
            $pinyins = $pinyinService->convert($name, PINYIN_NO_TONE);
            $letter = "";
            if (!empty($pinyins)) {
                $letter = strtoupper(substr($pinyins[0], 0, 1));
            }
            $data[] = [
                "code"   => $info['city_id'],
                "obj_id" => $info['city_id'],
                "city"   => $name,
                "letter" => $letter,
                "pinyin" => implode(" ", $pinyins),
                "zoom"   => 11,
                "center" => [
                    "type" => "Point",
                    "coordinates" => $point,
                ],
            ];
        }

        $this->response($data);
    }

}
