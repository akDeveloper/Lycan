<?php
namespace Lycan\Record;
/**
 * Database class [Object Based].
 *
 * @package    CMS v5
 * @author     Andreas Kollaros
 * @version    0.2 [15-11-2008 23:32:54]
 * @version    0.3 [03-12-2011 17:55:03]
 * @version    0.4 [13-01-2012 14:04:37]
 *
 * Database::fetch_all("SELECT * FROM `table` WHERE id = '?'", array(1));
 */
class Database {

    protected $debug;
    protected $last_query;
    protected $queries;
    protected $count;
    protected $host;
    protected $user;
    protected $password;
    protected $database;
    protected $database_type;
    protected $database_names;
    
    protected $dbase;

    private static $_handler;

    public static function createFrom($options)
    {
        self::$_handler = self::$_handler ?: new self($options);
        #return $this->handler(); 
    }

    public static function fetchAll($query, $args=array())
    {
        if (null === self::$_handler)
            throw new Exception('No database connetion found!');
        return self::$_handler->fetch_all($query, $args);
    }

    public static function fetchOne()
    {
         if (null === self::$_handler)
            throw new Exception('No database connetion found!');
        return self::$_handler->fetch_one($query, $args);       
    }

    public static function showDebug()
    {
        return self::$_handler->debug();
    }

    private function _clone()
    {
    }

    private function __construct($options) {

        $this->host = $options['host'];
        $this->user = $options['user'];
        $this->password = $options['password'];
        $this->database = $options['database'];
        $this->database_type = $options['type'];
        $this->database_names = $options['set_names'];

        $this->dbase = new \mysqli($this->host, $this->user, $this->password, $this->database);

        if ($this->dbase->connect_error)
            die("Could not connect to database server! [" . $this->dbase->connect_error . "]");

        $this->dbase->set_charset($this->database_names);
    }

    protected function fetch_all($query, $args = array(), $class=null, $params=array()) {
        
        $query = $this->_sanitize_query($query, $args);

        $data = array();

        $this->queries++;

        $start = microtime(true);
        $result = $this->dbase->query($query);

        $this->last_query[$this->queries] = "SQL ("
            . number_format((microtime(true) - $start) * 1000, '4')
            . "ms)  " . $query;
        if ($this->is_valid_query($result)) {
            $start = microtime(true);
            while ($row = $result->fetch_object()) {
                $data[] = $row;
            }
            $this->last_query[$this->queries] .= " :: Fetched data in ("
                . number_format((microtime(true) - $start) * 1000, '4') . "ms)";
            $this->count = $result->num_rows;
            $result->free();
            return $data;
        } else {
            $this->debug .= $this->dbase->error . ' -> ' . $this->queries;
        }
    }

    protected function fetch_one($query, $args = array(), $class=null, $params = array()) {
        
        $query = $this->_sanitize_query($query, $args);
        
        $data = array();
        
        $this->queries++;
        
        $start = microtime(true); 
        $result = $this->dbase->query($query);

        $this->last_query[$this->queries] = "SQL ("
            . number_format((microtime(true) - $start) * 1000, '4')
            . "ms)  " . $query;
        if ($this->is_valid_query($result)) {

            $start = microtime(true);
            $row = $result->fetch_object();

            $this->last_query[$this->queries] .= " :: Fetched data in ("
                . number_format((microtime(true) - $start) * 1000, '4') . "ms)";
            $result->free();
            return $row;
        } else {
            $this->debug .= $this->dbase->error . ' -> ' . $this->queries;
        }
    }

    protected function fetch_col($query) {
        $data = array();
        $result = $this->dbase->query($query);
        $this->queries++;
        $this->last_query[$this->queries] = $query;
        if ($this->is_valid_query($result)) {
            while ($row = $result->fetch_array()) {
                foreach ($row as $key => $value) {
                    if (!is_numeric($key)) {
                        $data[] = $value;
                    }
                }
            }
            $this->count = $result->num_rows;
            $result->free();
            return $data;
        } else {
            $this->debug .= $this->dbase->error . ' -> ' . $this->queries;
        }
    }

    protected function exec($query) {
        $this->queries++;
        $start = microtime(true);
        $result = $this->dbase->query($query);
        $this->last_query[$this->queries] = "SQL ("
            . number_format((microtime(true) - $start) * 1000, '4')
            . "ms)  " . $query;
        if (!$result) {
            $this->debug .= $this->dbase->error . ' -> ' . $this->queries;
            return false;
        } else {
            return $result;
        }
    }

    protected function is_valid_query($result) {
        if ($result != false) {
            if ($result->num_rows) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    protected function debug() {
        echo '<div style="border: 1px solid #000000;width:98%;float:left;padding: 4px;font-family:monospace;font-size: 11px;">';
        echo '<span style="font-weight:bold">Total queries :</span><span style="font-weight:bold;color:#FF0000">' . (int) $this->queries . '</span><br />';
        echo '<span style="font-weight:bold">DB queries</span><br />';
        if ( $this->last_query ){
            foreach ($this->last_query as $key => $value) {
                echo '[' . $key . ']<span style="color:#0000A5">' . $value . '</span><br />';
            }
        } else {
                echo '[0]<span style="color:#0000A5">NONE</span><br />';
        }
        echo '<span style="font-weight:bold">DB Errors</span><br />';
        echo $this->debug;
        echo '</div>';
    }

    private function _sanitize_query($query, $args)
    {
        if(empty($args)) return $query;
        $args = array_map(array($this->dbase, 'real_escape_string'), $args);
        $conditions = array($query);
        foreach($args as $arg){ $conditions[] = $arg; }
        $array_keys = array_keys($conditions);
        if (reset($array_keys) === 0 &&
            end($array_keys) === count($conditions) - 1 &&
            !is_array(end($conditions)))
        {
            $condition = " ( " . array_shift($conditions) . " ) ";
            foreach ($conditions as $value) {
                $value = "'$value'";
                $condition = preg_replace('|\?|', $value, $condition, 1);
            }
        }
        return $condition;
    }

//end class
}

?>
