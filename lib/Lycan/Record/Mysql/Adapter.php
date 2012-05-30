<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Mysql;

use \Lycan\Record\Logger;
    
class Adapter extends \Lycan\Record\Adapter
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
        
        $this->logger = new Logger();
    }

    /**
     * Establish a connection to database and return its instance
     *
     * @access public
     *
     * @return object \mysqli instance object
     */
    public function connect()
    {
        if (null === $this->connection) {
            $this->connection = new \mysqli($this->host, $this->user, $this->password, $this->database, $this->port);
            if ( $this->charset ) $this->connection->set_charset($this->charset);
            $this->logger->connectionLog(" Connected to " . $this->database . " FROM " . __CLASS__);
        }
        return $this->connection;
    }

    public function getConnection()
    {
        return $this->connection ?: $this->connect();
    }

    public function disconnect()
    {
        return $this->connection = null;
    }

    public function createQuery($class_name=null, $options = array())
    {
        return new Query($class_name, $options);
    }

    public function execute(\Lycan\Record\Query $query, $action='Load')
    {
        $start = microtime(true);
        $res = $this->getConnection()->query($query->__toString());        

        $this->logger->logQuery($query, $query->getClassName(), microtime(true) - $start, $action);

        if (!$res)
            throw new \Exception("Error executing query: " . $query . "\n" . $this->getConnection()->error);

        #$rows = array();
        #$class = $query->getClassName();
        if ($res instanceof \mysqli_result) {

            #$start = microtime(true);
            
            #$rows = $res->fetch_all(MYSQLI_ASSOC);
            
            #$this->logger->logFetchTime(microtime(true) - $start);

            #$res->free();
            #return $rows;
            return $res;
        } else {
            return $res;
        }
    }

    public function insert(\Lycan\Record\Query $query)
    {
        $res = $this->execute($query, 'Create');
        return $res ? $this->getConnection()->insert_id : false;
    }

    public function update(\Lycan\Record\Query $query)
    {
        $res = $this->execute($query, 'Update');
        return $res !== false ? $this->getConnection()->affected_rows : false;
    }
    public function rawSql($sql)
    {
        $start = microtime(true);
        $res = $this->getConnection()->query($sql);        

        $this->logger->logQuery($sql, 'Raw', microtime(true) - $start);

        if (!$res)
            throw new \Exception("Error executing query: " . $sql . "\n" . $this->connection()->error);
        
        return $res;
    }

    public function escapeString($string)
    {
        return $this->getConnection()->real_escape_string($string);
    }

    public function unapostrophe($string)
    {
        if ( substr_count($string, '`') == 2 ) 
            return str_replace('`', '', $string);

        return $string;
    }

    public function apostrophe($string)
    {
        if ( substr_count($string, '`') == 2 ) return $string;

        return '`' . $string . '`';
    }

    public function quote($string)
    {
        if ( substr_count($string, "'") == 2 ) return $string;

        return "'" . $string . "'";       
    }

}
