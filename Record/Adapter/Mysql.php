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

    protected function connection()
    {
        if ( null === self::$connection || null === $this->logger) {
            self::$connection = new \mysqli($this->host, $this->user, $this->password, $this->database, $this->port);
            if ( $this->charset ) self::$connection->set_charset($this->charset);
            $filename = APP_PATH . "log" . DS . ENV . ".log";
            $this->logger = new \Lycan\Record\Logger($filename);
            $this->logger->log(" Connected to " . $this->database . " FROM " . __CLASS__);
        }
        return self::$connection;
    }

    public function getQuery($class_name=null, $options = array())
    {
        return new \Lycan\Record\Query\Mysql($class_name, $options);
    }

    public function escapeString($string)
    {
        return $this->connection()->real_escape_string($string);
    }

    public function query(\Lycan\Record\Query $query)
    {
        $start = microtime(true);
        $res = $this->connection()->query($query->getQuery());        

        $this->logger->logQuery($query, $query->getClassName(), microtime(true) - $start);

        if (!$res)
            throw new \Exception("Error executing query: " . $query . "\n" . $this->connection()->error);

        $rows = array();
        $class = $query->getClassName();
        if ( $res instanceof \mysqli_result ) {

            $start = microtime(true);
            while ($row = $res->fetch_assoc())
                $rows[] = $class::initWith($row);

            $this->logger->logFetchTime(microtime(true) - $start);

            $res->free();
            return $rows;
        } else {
            return $res;
        }
    }

    public function insert(\Lycan\Record\Query $query)
    {
        $res = $this->query($query);
        return $res ? $this->connection()->insert_id : false;
    }

}
