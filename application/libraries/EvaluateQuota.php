<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/5/14
 * Time: 下午5:35
 */

/**  root directory */
if (!defined('EVALUATE_QUOTA_ROOT')) {
    define('EVALUATE_QUOTA_ROOT', dirname(__FILE__) . '/');

    require(EVALUATE_QUOTA_ROOT . 'evaluateQuota/Autoloader.php');
}

if (!defined('EVALUATE_QUOTA_INFO')){
    define('EVALUATE_QUOTA_INFO',EVALUATE_QUOTA_ROOT. 'evaluateQuota/QuotaInfo'.DIRECTORY_SEPARATOR);
}

class EvaluateQuota
{
    private $_evaluateQuotaFactory;

    private function _initQuotaFactory(EvaluateQuotaFactory $factory)
    {
        $this->_evaluateQuotaFactory = $factory;
    }

    private function _getAreaQuotaFactory()
    {
        $this->_initQuotaFactory(new AreaQuota());
        return $this->_evaluateQuotaFactory;
    }
    private function _getJunctionQuotaFactory()
    {
        $this->_initQuotaFactory(new JunctionQuota());
        return $this->_evaluateQuotaFactory;
    }

    private function _getCityQuotaFactory()
    {
        $this->_initQuotaFactory(new CityQuota());
        return $this->_evaluateQuotaFactory;
    }

    private function _camelize($uncamelized_words,$separator='_')
    {
        $uncamelized_words = $separator. str_replace($separator, " ", strtolower($uncamelized_words));
        return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator );
    }

    private function _uncamelize($camelCaps,$separator='_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }


    /**
     * 调用方式:驼峰命名 get +指标类型(City,Junction)+指标名称(durationDelay)
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        $name = $this->_uncamelize($name);
        $feature = explode('_',$name);
        $factory = null;
        switch ($feature[1]){
            case 'city':
                $factory = $this->_getCityQuotaFactory();
                break;
            case 'road':
                $factory = $this->_getRoadQuotaFactory();
                break;
            case 'junction':
                $factory = $this->_getJunctionQuotaFactory();
                break;
            case 'area':
                $factory = $this->_getAreaQuotaFactory();
                break;
            default:
                throw new Exception('undefined quota type');

        }
        if(empty($arguments[0])){
            throw new Exception('quota factory can not load data which is null');
        }

        $factory->load_data($arguments[0]);
        $funcName = 'get';
        for($i = 2 ;$i<count($feature);$i++){
            $funcName.="_".$feature[$i];
        }
        $funcName = self::_camelize($funcName);
        unset($arguments[0]);
        $args = $arguments;
        if(method_exists($factory,$funcName)){
            return call_user_func_array([$factory, $funcName], $args);
        }

    }


    /**===============================路口指标相关========================================*/


//
//    /**
//     * 单个路口延误时间详情
//    */
//    public function getJunctionDurationDelay($data,$start_time,$end_time)
//    {
//        $factory = $this->_getJunctionQuotaFactory();
//        $factory->load_data($data);
//        $ret = $factory->getDurationDelay($start_time,$end_time);
//        return $ret;
//    }
//
//
//
//    /**===============================城市指标相关========================================*/
//
//    /**
//     * 全城路口延误时间概况
//     */
//    public function getCityDurationDelay($data)
//    {
//        $factory = $this->_getCityQuotaFactory();
//        $factory->load_data($data);
//        $ret = $factory->getDurationDelay();
//        return $ret;
//    }
//
//    /**
//     * 全城停车次数概况
//     */
//    public function getCityStopCount($data)
//    {
//        $factory = $this->_getCityQuotaFactory();
//        $factory->load_data($data);
//        $ret = $factory->getStopCount();
//        return $ret;
//    }

    /**===============================路段指标相关========================================*/



    /**===============================区域指标相关========================================*/



}

