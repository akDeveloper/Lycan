<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Adapter;

class Mysql extends \Lycan\Record\Adapter
{
    /**
     * Logger to log database queries
     *
     * @var \Lycan\Support\Logger
     * @access protected
     */
    protected $logger;

    public function __construct($options)
    {
        $this->host           = $options['host'];
        $this->port           = $options['port'];
        $this->user           = $options['user'];
        $this->password       = $options['password'];
        $this->database       = $options['database'];
        $this->charset        = $options['charset'];
    }

    protected function connect()
    {
        if ( null === self::$connection ) {
            self::$connection = new \mysqli($this->host, $this->user, $this->password, $this->database, $this->port);
            if ( $this->charset ) self::$connection->set_charset($this->charset);
            $filename = APP_PATH . "log" . DS . ENV . ".log";
            $this->logger = new \Lycan\Record\Logger($filename);
        }
        return self::$connection;
    }

    public function getQuery($class_name, $options = array())
    {
        return new \Lycan\Record\Query\Mysql($class_name, $options);
    }

    public function escapeString($string)
    {
        return $this->connect()->real_escape_string($string);
    }

    public function query(\Lycan\Record\Query $query)
    {
        $start = microtime(true);
        $res = $this->connect()->query($query->getQuery());        

        $this->logger->logQuery($query, $query->getClassName(), microtime(true) - $start);

        if (!$res)
            throw new \Exception("Error executing query: " . $query . "\n" . $this->connect()->error);

        $rows = array();
        if ( $res !== true ) {

            $start = microtime(true);
            while ($row = $res->fetch_object())
                $rows[] = $row;

            $this->logger->logFetchTime(microtime(true) - $start);

            $res->free();
            return $rows;
        } else {
            return $res;
        }
    }

    public function insert()
    {
         
    }

}
