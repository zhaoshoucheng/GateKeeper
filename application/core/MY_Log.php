<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Logging Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Logging
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/general/errors.html
 */
class MY_Log extends CI_Log {

    protected $_log_path;
    protected $_threshold	= 1;
    protected $_date_fmt	= 'Y-m-d H:i:s';
    protected $_enabled	= TRUE;

    //已有代码中使用的
    protected $_levels	= array('ERROR' => '2', 'DEBUG' => '16',  'INFO' => '16', 'ALL' => '16');

    const LOG_FATAL     = 1; //致命错误，已经影响到程序的正常进行
    const LOG_WARNING   = 2; //可能存在错误，但是不影响程序的正常进行
    const LOG_STRACE    = 3; //特定日志级别,记录trace信息同时配合采样逻辑。 放在warning和notice日志等级间
    const LOG_NOTICE    = 4; //往往用于统计一次请求的整体状况 
    const LOG_TRACE     = 8; //调试日志
    const LOG_DEBUG     = 16; //调试日志，线上严禁打开
    static $LOG_NAME = array (  //用于在日志中输出
        self::LOG_FATAL   => 'FATAL',
        self::LOG_WARNING => 'WARNING',
        self::LOG_STRACE => 'STRACE',
        self::LOG_NOTICE  => 'NOTICE',
        self::LOG_TRACE   => 'TRACE',
        self::LOG_DEBUG   => 'DEBUG'
    );
    private $basic_info = array();
    private $basic_info_str = '';

    private $wflog_str = '';    //wf log buffer
    private $log_str = '';      //非wf log buffer
    const PAGE_SIZE     = 4096;

    /**
     * Constructor
     */
    public function __construct()
    {
        $config =& get_config();

        $this->_log_path = ($config['log_path'] != '') ? $config['log_path'] : APPPATH.'logs/';

        if ( ! is_dir($this->_log_path) OR ! is_really_writable($this->_log_path))
        {
            $this->_enabled = FALSE;
        }

        if (is_numeric($config['log_threshold']))
        {
            $this->_threshold = $config['log_threshold'];
        }

        if ($config['log_date_format'] != '')
        {
            $this->_date_fmt = $config['log_date_format'];
        }
    }

    // --------------------------------------------------------------------

    /**
     * Write Log File
     *
     * Generally this function will be called using the global log_message() function
     *
     * @param	string	the error level
     * @param	string	the error message
     * @param	bool	whether the error is a native PHP error
     * @return	bool
     */
    public function write_log($level = 'error', $msg='', $php_error = FALSE)
    {
        if ($this->_enabled === FALSE)
        {
            return FALSE;
        }

        $level = strtoupper($level);

        //将原日志系统映射到现有日志系统中,屏蔽原日志系统
        if(isset($this->_levels[$level])){
            $this->__log__($this->_levels[$level],array($msg));
            $this->flush();
            return TRUE;
        }
        return FALSE;
    }

    protected function get_file_line($caller)
    {
        $file_line = '';
        if(isset($caller['line'])) {
            $file_line = $caller['file'].' +'.$caller['line'];
        }
        $func = '';
        if(isset($caller['function'])) {
            $func = '::'.$caller['function'];
            if(isset($caller['class'])) {
                $func = $caller['class'].$func;
            }
        }
        if(empty($file_line)) $file_line = '?';
        if(empty($func)) $func = '?';
        return "line=$file_line function=$func";
    }

    private function get_caller_file_line($caller)
    {
        $file_line = '';
        if(isset($caller['line'])) {
            $file_line = $caller['file'].' +'.$caller['line'];
        }

        if(empty($file_line)) $file_line = '?';

        return "line=$file_line";
    }

    private function get_caller_class_name($caller)
    {
        $class = '';
        if (isset($caller['class'])) {
            $class = $caller['class'];
        }
        if (empty($class)) $class = "?";
        return "class=$class";
    }

    private function get_caller_function_name($caller)
    {
        $func = '';
        if(isset($caller['function'])) {
            $func = '::'.$caller['function'];
            if(isset($caller['class'])) {
                $func = $caller['class'].$func;
            }
        }
        if(empty($func)) $func = '?';
        return "function=$func";
    }

    public function write_file($log_file,$log_str)
    {
        //file_put_contents($log_file,$log_str,FILE_APPEND);
        $fd = @fopen($log_file, "a+" );
        if (is_resource($fd)) {
            fputs($fd, $log_str);
            fclose($fd);
        }
    }

    public function write($force_flush)
    {
        if ($force_flush || strlen($this->log_str)>self::PAGE_SIZE){
            if (!empty($this->log_str)) {
                $normal_log_path = $this->_log_path.'didi.log';
                $this->write_file($normal_log_path, $this->log_str);
                $this->log_str   = '';
            }
        }

        if($force_flush || strlen($this->wflog_str)>self::PAGE_SIZE ) {
            if (!empty($this->wflog_str)) {
                $wflog_path = $this->_log_path."didi.log.wf";
                $this->write_file($wflog_path, $this->wflog_str);
                $this->wflog_str = '';
            }
        }
    }

    //===== [Extra Add][E] =====
    //Added by liuxuewen@diditaxi.com.cn  2016-03-09 18:18:18
    public function write_track_log($track)
    {
        $trackAsJson = json_encode($track, JSON_PARTIAL_OUTPUT_ON_ERROR);
        $logFileName = $this->_log_path . 'track.log';
        $this->write_file($logFileName, $trackAsJson . "\n");
    }
    //===== [Extra Add][E] =====

    //public function  __log__($log_level,$arr)
    public function  __log__($log_level,$arr,$dltag='_undef')
    {
        //日志已关闭
        if ($this->_enabled === FALSE) return;

        //日志级别限制
        if($log_level > $this->_threshold) return;

        //获取打印日志 文件和行号
        $bt = debug_backtrace();
        //$file_line_str = $this->get_file_line($bt[2]);
        $file_line = $this->get_caller_file_line($bt[2]);
        $class = $this->get_caller_class_name($bt[3]);
        $function = $this->get_caller_function_name($bt[3]);
        $file_line_str = $file_line.' '.$class.' '.$function;
        $micro = microtime();
        $sec = intval(substr($micro, strpos($micro," ")));
        //$ms = floor($micro*1000000);    //微妙
        $us = floor($micro*1000000);    //微妙
        $ms = floor($micro*1000);       //毫秒
        //初始化本条日志串
        //$str = sprintf( "[%s][%s:%-06d][%s][", self::$LOG_NAME[$log_level], date("Y-m-d H:i:s",$sec), $ms, $file_line_str);
        $str = sprintf( "[%s][%s.%3d%s][%s] %s||", self::$LOG_NAME[$log_level], date("Y-m-d\\TH:i:s",$sec), $ms, date("O"), $file_line_str, $dltag);

        //===== [Extra Change][S] =====
        //Changed by liuxuewen@diditaxi.com.cn  2016-03-09 18:18:18
        //初始化basic_info串，包含logid uri等
        //在add_basic中更新
        //$str .= $this->basic_info_str;

        //日志太大，只在notice中记录basic_info信息
        if($log_level == self::LOG_NOTICE){
            $str .= $this->basic_info_str;
        }else {
            $strLogid = isset($this->basic_info['logid']) ? $this->basic_info['logid'] : '';
            $strTraceid = isset($this->basic_info['traceid']) ? $this->basic_info['traceid'] : '';
            $strSpanid = isset($this->basic_info['spanid']) ? $this->basic_info['spanid'] : '';
            $strPSpanid = isset($this->basic_info['pspanid']) ? $this->basic_info['pspanid'] : '';
            //$str       .= sprintf( "traceid=%s][pspanid=%s][spanid=%s][logid=%s][", $strTraceid, $strPSpanid, $strSpanid, $strLogid );

            if (strlen($strTraceid) == 33) {
                $strTraceid = substr($strTraceid, 0, 32);
                $strIsSampling = substr($strTraceid, 32, 1);
                $str .= sprintf("traceid=%s||pspanid=%s||spanid=%s||logid=%s||sampling=%s||", $strTraceid, $strPSpanid, $strSpanid, $strLogid, $strIsSampling);
            } else {
                $str .= sprintf("traceid=%s||pspanid=%s||spanid=%s||logid=%s||", $strTraceid, $strPSpanid, $strSpanid, $strLogid);
            }

            $strOrderid = isset($this->basic_info['order_id']) ? $this->basic_info['order_id'] : '';
            $strSOrderid = isset($this->basic_info['s_order_id']) ? $this->basic_info['s_order_id'] : '';
            $strPassengerId = isset($this->basic_info['passenger_id']) ? $this->basic_info['passenger_id'] : '';
            $strPassengerPhone = isset($this->basic_info['passenger_phone']) ? $this->basic_info['passenger_phone'] : '';
            $strDriverId = isset($this->basic_info['driver_id']) ? $this->basic_info['driver_id'] : '';
            $strDriverPhone= isset($this->basic_info['driver_id']) ? $this->basic_info['driver_phone'] : '';

            $str .= sprintf("order_id=%s||s_order_id=%s||passenger_id=%s||passenger_phone=%s||driver_id=%s||driver_phone=%s||", $strOrderId, $strSOrderId, $strPassengerId, $strPassengerPhone, $strDriverId, $strDriverPhone);

        }
        //===== [Extra Change][E] =====
        /*
        $biz_message = '';
        $CI =& get_instance();
        if (property_exists($CI, biz_log_record)  && !empty($CI->biz_log_record))  {
            if ( is_array($CI->biz_log_record) ) {
                foreach ($CI->biz_log_record as $key => $value) {
                    if (! array_key_exists($key, $this->basic_info)) {
                        $tmp[] = "$key=$value";
                    }

                }
            }
            $biz_message = implode('||', $tmp);
        }
        $str .= $biz_message.'||';
        */

        $format = $arr[0];
        array_shift($arr);
        if (empty($arr)) {
            $str .= $format;
        } else {
            $str .= vsprintf($format, $arr);
        }

        switch ($log_level) {
            case self::LOG_WARNING :
            case self::LOG_FATAL :
                //$this->wflog_str .= $str . "]\n";
                $this->wflog_str .= $str . "\n";
                break;
            case self::LOG_DEBUG :
            case self::LOG_TRACE :
            case self::LOG_NOTICE :
            case self::LOG_STRACE :
                //$this->log_str .= $str . "]\n";
                $this->log_str .= $str . "\n";
                break;
            default :
                break;
        }
        $this->write(false);
    }

    //public function fatal($args)
    public function fatal($args, $dltag='_undef')
    {
        //$this->__log__(self::LOG_FATAL,$args);
        $this->__log__(self::LOG_FATAL,$args, $dltag);
    }
    //public function warning($args)
    public function warning($args, $dltag='_undef')
    {
        $this->__log__(self::LOG_WARNING,$args, $dltag);
    }
    public function strace($args, $dltag='_undef')
    {
        $this->__log__(self::LOG_STRACE, $args, $dltag);
    }
    //public function notice($args)
    public function notice($args, $dltag='_undef')
    {
        //$this->__log__(self::LOG_NOTICE,$args);
        $this->__log__(self::LOG_NOTICE, $args, $dltag);
    }
    public function trace($args)
    {
        $this->__log__(self::LOG_TRACE,$args);
    }
    public function debug($args)
    {
        $this->__log__(self::LOG_DEBUG,$args);
    }

    public function flush()
    {
        $this->write(true);
    }

    public function add_basic($basic_info)
    {
        $this->basic_info = array_merge($this->basic_info,$basic_info);
        $this->basic_info_str = '';
        foreach($this->basic_info as $key => $value) {
            //===== [Extra Change][S] =====
            //Changed by liuxuewen@diditaxi.com.cn  2016-03-11 19:36:28
            //$info = is_array($value) ? 'Array info' : $value;
            ////$this->basic_info_str .= $key.'='.$value.']['; 
            //$this->basic_info_str .= $key.'='.$info.']['; 

            //$this->basic_info_str .= $key.'='.$value.'||';
            $info = is_array($value) ? json_encode($value, JSON_PARTIAL_OUTPUT_ON_ERROR) : $value;
            $this->basic_info_str .= $key.'='.$info.'||';
            //===== [Extra Change][E] =====
        }
    }

}
// END Log Class
/* End of file Log.php */
/* Location: ./system/libraries/Log.php */
