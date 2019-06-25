<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Overtrue\Pinyin\Pinyin;
class City extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model("openCity_model");
    }

    public function list()
    {
        // 获取citylist,然后获取拼音
        $cityInfos = $this->openCity_model->getCityInfos();
        if (empty($cityInfos)) {
            $this->errno = ERR_DATABASE;
            $this->errmsg = "获取开城城市列表失败";
            return;
        }
        $permCitys = $this->permCitys;
        $data = [];
        $pinyinService = new Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
        foreach ($cityInfos as $info) {
            $cityId = intval($info['city_id']);
            if (!empty($permCitys) && !in_array($cityId, $permCitys)) {
                continue;
            }
            $center = $info['center_point'];
            $point = explode(",", $center);
            $point = array_map(function($item) {
                return (float)$item;
            }, $point);
            $name = $info['city_name'];
            $pinyins = $pinyinService->convert($name, PINYIN_NO_TONE);
            $letter = "";
            if (!empty($pinyins)) {
                $letter = strtoupper(substr($pinyins[0], 0, 1));
            }
            $data[] = [
                "code"   => $cityId,
                "obj_id" => $cityId,
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
