<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Support;

class Logger extends \SplFileObject
{
    const RED    = '1;31m';
    const GREEN  = '1;32m';
    const PURPLE = '1;35m';
    const CYAN   = '1;36m';
    const WHITE  = '1;37m';

    const RESET_SEQ = "\033[0m";
    const COLOR_SEQ = "\033[";
    const BOLD_SEQ  = "\033[1m";

    private static $start_time;

    private static $memory;

    protected static $logger_instance;

    public static function startLogging()
    {
        self::$start_time = microtime(true);
        self::$memory = memory_get_usage(true);
        $buffer = self::COLOR_SEQ . self::GREEN
                . "Started at : [" . date('H:i:s d-m-Y', time()) . "]"
                . self::RESET_SEQ;
        static::getLogger()->log($buffer);
    }

    public static function stopLogging()
    {
        $buffer = self::COLOR_SEQ . self::GREEN . "Completed in "
            . number_format((microtime(true) - self::$start_time) * 1000, 0)
            . "ms | "
            . "Mem Usage: ("
            . number_format( (memory_get_usage(true) - self::$memory) / (1024), 0, ",", "." )
            ." kb)"
            . self::RESET_SEQ;
        static::getLogger()->log($buffer);
    }

    public static function getLogger($env=null, $open_mode="a")
    {
        if (static::$logger_instance) return static::$logger_instance; 
        $env = $env ?: ENV;
        $filename = APP_PATH . 'log' . DS . $env . '.log';
        static::$logger_instance = new static($filename,$open_mode);
        return static::$logger_instance; 
    }

    public function __construct($filename=null, $open_mode = "a")
    {
        $filename = $filename ?: APP_PATH . "log" . DS . ENV . ".log";
        parent::__construct($filename, $open_mode);
    }

    public function log($string)
    {
        $this->fwrite($string . "\n");
    }

    public function errorLog($string)
    {
        $this->log(COLOR_SEQ . "1;37m" . "!! WARNING: " . $string . RESET_SEQ);
    }
}
