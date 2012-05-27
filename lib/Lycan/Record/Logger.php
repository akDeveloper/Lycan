<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

class Logger extends \Lycan\Support\Logger
{
    public function logQuery($query, $class_name=null, $parse_time = 0, $action='Load')
    {
        $buffer = self::COLOR_SEQ . self::PURPLE . "$class_name $action ("
            . number_format($parse_time * 1000, '4')
            . "ms)  " . self::RESET_SEQ . self::COLOR_SEQ . self::WHITE
            .   $query . self::RESET_SEQ;

        $this->log($buffer);
    }

    public function logFetchTime($parse_time=0)
    {
        $buffer = self::COLOR_SEQ . self::CYAN . " Fetched data in ("
            . number_format($parse_time * 1000, '4')
            . "ms)  " . self::RESET_SEQ;

        $this->log($buffer);       
    }

    public function connectionLog($string)
    {
        $buffer = self::COLOR_SEQ . self::PURPLE 
            . $string
            . self::RESET_SEQ;
        $this->log($buffer);
    }
}
