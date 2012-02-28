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
    public $logger;

    public function __construct($options, $pool='default')
    {
        $this->host           = $options['host'];
        $this->port           = $options['port'];
        $this->user           = $options['user'];
        $this->password       = $options['password'];
        $this->database       = $options['database'];
        $this->charset        = $options['charset'];
        
        $this->logger = new \Lycan\Record\Logger();
    }

    /**
     * Establish a connection to database and retyrn its instance
     *
     * @access protected
     *
     * @return object \mysqli instance object
     */
    protected function connection()
    {
        if ( null === $this->connection || null === $this->logger) {
            $this->connection = new \mysqli($this->host, $this->user, $this->password, $this->database, $this->port);
            if ( $this->charset ) $this->connection->set_charset($this->charset);
            $this->logger->connectionLog(" Connected to " . $this->database . " FROM " . __CLASS__);
        }
        return $this->connection;
    }


    public function getQuery($class_name=null, $options = array())
    {
        return new \Lycan\Record\Query\MySql($class_name, $options);
    }

    /**
     * Escapes a string to performa a safe sql query
     *
     * @param string $string the string to escape
     * @access public
     * @abstract
     *
     * @return string the escaped string
     */
    public function escapeString($string)
    {
        return $this->connection()->real_escape_string($string);
    }

    public function query(\Lycan\Record\Query $query)
    {
        $start = microtime(true);
        $res = $this->connection()->query($query->__toString());        

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

    public function rawSql($sql)
    {
        $start = microtime(true);
        $res = $this->connection()->query($sql);        

        $this->logger->logQuery($sql, 'Raw', microtime(true) - $start);

        if (!$res)
            throw new \Exception("Error executing query: " . $sql . "\n" . $this->connection()->error);
        
        return $res;
    }

}
