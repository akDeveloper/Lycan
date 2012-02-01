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
    
    protected $includes;

    protected $class_name;

    protected $fetch_method;

    protected $as_object;

    protected $query;

    protected $bind_params;
    
    public function __construct($class_name=null, $options = array())
    {
        if ($class_name) {
            $this->class_name = $class_name;
            
            $this->table = $class_name::$table;
            
            $this->fetch_method = isset($options['fetch_method'])
                ? $options['fetch_method']
                : 'all';
            $this->as_object = isset($options['as_object'])
                ? $options['as_object']
                : false;
        }
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getBindParams()
    {
        return $this->bind_params;
    }

    public function getClassName()
    {
        return $this->class_name;
    }

    protected function adapter()
    {
        $class = $this->class_name;
        return $class::getAdapter();
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
    
    abstract public function fetch();

    public function __toString()
    {
        return $this->query;
    }
}
