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
}

