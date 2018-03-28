<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package        CodeIgniter
 * @author        ExpressionEngine Dev Team
 * @copyright    Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license        http://codeigniter.com/user_guide/license.html
 * @link        http://codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Logging Class
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Logging
 * @author        ExpressionEngine Dev Team
 * @link        http://codeigniter.com/user_guide/general/errors.html
 */
class MY_Log extends CI_Log
{

    protected $_log_path;
    protected $_threshold = 1;
    protected $_date_fmt = 'Y-m-d H:i:s';
    protected $_enabled = TRUE;

    //已有代码中使用的
    protected $_levels = array('ERROR' => 1, 'NOTICE' => 2, 'DEBUG' => 3, 'ALL' => 4);

    const LOG_ERROR = 1; //存在错误
    const LOG_NOTICE = 2; //往往用于统计一次请求的整体状况
    const LOG_DEBUG = 3; //调试日志，线上严禁打开
    static $LOG_NAME = array(  //用于在日志中输出
        self::LOG_ERROR => 'ERROR',
        self::LOG_NOTICE => 'NOTICE',
        self::LOG_DEBUG => 'DEBUG'
    );
    private $basic_info = array();
    private $basic_info_str = '';

    private $wflog_str = '';    //wf log buffer
    private $log_str = '';      //非wf log buffer
    const PAGE_SIZE = 4096;

    /**
     * Constructor
     */
    public function __construct()
    {
        $config =& get_config();

        $this->_log_path = ($config['log_path'] != '') ? $config['log_path'] : APPPATH . 'logs/';

        if (!is_dir($this->_log_path) OR !is_really_writable($this->_log_path)) {
            $this->_enabled = FALSE;
        }

        if (is_numeric($config['log_threshold'])) {
            $this->_threshold = $config['log_threshold'];
        }

        if ($config['log_date_format'] != '') {
            $this->_date_fmt = $config['log_date_format'];
        }
    }

    // --------------------------------------------------------------------

    /**
     * Write Log File
     *
     * Generally this function will be called using the global log_message() function
     *
     * @param    string    the error level
     * @param    string    the error message
     * @param    bool    whether the error is a native PHP error
     * @return    bool
     */
    public function write_log($level = 'error', $msg = '', $php_error = FALSE)
    {
        if ($this->_enabled === FALSE) {
            return FALSE;
        }

        $level = strtoupper($level);

        //将原日志系统映射到现有日志系统中,屏蔽原日志系统
        if (isset($this->_levels[$level])) {
            $this->__log__($this->_levels[$level], array($msg));
            $this->flush();
            return TRUE;
        }
        return FALSE;
    }

    protected function get_file_line($caller)
    {
        $file_line = '';
        if (isset($caller['line'])) {
            $file_line = $caller['file'] . ' +' . $caller['line'];
        }
        $func = '';
        if (isset($caller['function'])) {
            $func = '::' . $caller['function'];
            if (isset($caller['class'])) {
                $func = $caller['class'] . $func;
            }
        }
        if (empty($file_line)) $file_line = '?';
        if (empty($func)) $func = '?';
        return "line=$file_line function=$func";
    }

    private function get_caller_file_line($caller)
    {
        $file_line = '';
        if (isset($caller['line'])) {
            $file_line = $caller['file'] . ' +' . $caller['line'];
        }

        if (empty($file_line)) $file_line = '?';

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
        if (isset($caller['function'])) {
            $func = '::' . $caller['function'];
            if (isset($caller['class'])) {
                $func = $caller['class'] . $func;
            }
        }
        if (empty($func)) $func = '?';
        return "function=$func";
    }

    public function write_file($log_file, $log_str)
    {
        //file_put_contents($log_file,$log_str,FILE_APPEND);
        $fd = @fopen($log_file, "a+");
        if (is_resource($fd)) {
            fputs($fd, $log_str);
            fclose($fd);
        }
    }

    public function write($force_flush)
    {
        if ($force_flush || strlen($this->log_str) > self::PAGE_SIZE) {
            if (!empty($this->log_str)) {
                $normal_log_path = $this->_log_path . 'didi.log';
                $this->write_file($normal_log_path, $this->log_str);
                $this->log_str = '';
            }
        }

        if ($force_flush || strlen($this->wflog_str) > self::PAGE_SIZE) {
            if (!empty($this->wflog_str)) {
                $wflog_path = $this->_log_path . "didi.log.wf";
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
    public function __log__($log_level, $arr)
    {
        //日志已关闭
        if ($this->_enabled === FALSE) return;

        //日志级别限制
        if ($log_level > $this->_threshold) return;

        //获取打印日志 文件和行号
        $bt = debug_backtrace();
        $file_line = $this->get_caller_file_line($bt[2]);
        $class = $this->get_caller_class_name($bt[3]);
        $function = $this->get_caller_function_name($bt[3]);
        $file_line_str = $file_line . ' ' . $class . ' ' . $function;
        $micro = microtime();
        $sec = intval(substr($micro, strpos($micro, " ")));
        //$ms = floor($micro*1000000);    //微妙
        $us = floor($micro * 1000000);    //微妙
        $ms = floor($micro * 1000);       //毫秒
        $strLogid = get_logid();
        $strUri = 'localhost';
        if (isset($_SERVER['REQUEST_URI'])) {
            $strUri = $_SERVER['REQUEST_URI'];            
        }
        //初始化本条日志串
        $str = sprintf("[%s][%s.%s.%s][%s][logid=%s][uri=%s]", self::$LOG_NAME[$log_level], date($this->_date_fmt, $sec), $ms, $us, $file_line_str,$strLogid,$strUri);

        //日志太大，只在notice中记录basic_info信息

        if ($log_level == self::LOG_NOTICE) {
            $str .= $this->basic_info_str;
        }
        $format = $arr[0];
        array_shift($arr);
        if (empty($arr)) {
            $str .= $format;
        } else {
            $str .= vsprintf($format, $arr);
        }

        switch ($log_level) {
            case self::LOG_ERROR :
                $this->wflog_str .= $str . "\n";
                break;
            case self::LOG_DEBUG :
            case self::LOG_NOTICE :
                $this->log_str .= $str . "\n";
                break;
            default :
                break;
        }
        $this->write(false);
    }

    public function flush()
    {
        $this->write(true);
    }

    public function add_basic($basic_info)
    {
        $this->basic_info = array_merge($this->basic_info, $basic_info);
        $this->basic_info_str = '';
        foreach ($this->basic_info as $key => $value) {
            $info = is_array($value) ? 'Array info' : $value;
            $this->basic_info_str .= $key . '=' . $info . '||';
        }
    }
}
// END Log Class
/* End of file Log.php */
/* Location: ./system/libraries/Log.php */
