<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

abstract class Query 
{
    protected $select;

    protected $where;

    protected $count;

    protected $limit;
    
    protected $offset = 0;

    protected $from;
    
    protected $table;

    protected $class_name;

    protected $fetch_method;

    protected $as_object;

    protected $query;
    
    public function __construct($class_name, $options = array())
    {
        $this->class_name = $class_name;
        $this->table = $class_name::$table;
        $this->fetch_method = isset($options['fetch_method'])
            ? $options['fetch_method']
            : 'all';
        $this->as_object = isset($options['as_object'])
            ? $options['as_object']
            : false;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getClassName()
    {
        return $this->class_name;
    }

    protected function adapter()
    {
        $class = $this->class_name;
        return $class::$adapter;
    }

    abstract public function select($args);

    abstract public function where($condition, $operator='AND');

    abstract public function count($field=null, $as=null);

    abstract public function limit($count);
    
    abstract public function offset($count);

    abstract public function from($table);

    abstract public function group($args);

    abstract public function order($args);

    abstract public function all();
    
    abstract public function first();
    
    abstract public function last();

    public function __toString()
    {
        return $this->query;
    }
}
