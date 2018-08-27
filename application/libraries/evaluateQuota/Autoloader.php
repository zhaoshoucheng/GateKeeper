<?php
EvaluateQuota_Autoloader::register();
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/5/14
 * Time: 下午4:35
 */
class EvaluateQuota_Autoloader
{
    public static function register()
    {

        if (function_exists('__autoload')) {
            // Register any existing autoloader function with SPL, so we don't get any clashes
            spl_autoload_register('__autoload');
        }
        // Register ourselves with SPL
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            return spl_autoload_register(array('EvaluateQuota_Autoloader', 'load'), true, true);
        } else {
            return spl_autoload_register(array('EvaluateQuota_Autoloader', 'load'));
        }
    }


    /**
     * Autoload a class identified by name
     *
     * @param    string    $pClassName        Name of the object to load
     */
    public static function load($pClassName)
    {
        if (class_exists($pClassName, false)) {
            // Either already loaded, or not a PHPExcel class request
            return false;
        }

        $pClassFilePath = EVALUATE_QUOTA_ROOT . 'evaluateQuota' .DIRECTORY_SEPARATOR;

        if(strpos($pClassName, 'QuotaInfo') !== false){
            $pClassFilePath .= "QuotaInfo".DIRECTORY_SEPARATOR;

        }

        $pClassFilePath.= $pClassName.'.php';

        if ((file_exists($pClassFilePath) === false) || (is_readable($pClassFilePath) === false)) {
            // Can't load
            return false;
        }

        require($pClassFilePath);
    }
}