<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Query;

use Lycan\Support\Inflect;

class Mysql extends \Lycan\Record\Query
{
    private $_select_values;
    private $_tables_for_join;

    protected $having;
    protected $joins=array();
    protected $join_queries;

    public function select($args)
    {
        if ( empty($args) || null == $args )
            throw new \InvalidArgumentException('Invalid arguments supplied.');

        $args = is_array($args) ? implode(',', $args) : $args;
        empty($this->select) 
            ? $this->select .= $args
            : $this->select .= ", " . $args;
        
        return $this;       
    }

    public function where($conditions, $operator='AND')
    {
        if (is_string($conditions)) {
            /* Example:
             * ->where("id = '1' or username = 'John'")
             */
            $_condition = $conditions;
        } elseif (is_array($conditions)) {
            /* Example:
             * ->where(array('id = ? and username = ?', '1', 'John') )
             */
            $array_keys = array_keys($conditions);
            if (reset($array_keys) === 0 &&
                end($array_keys) === count($conditions) - 1 &&
                !is_array(end($conditions))) 
            {
                $condition = " ( " . array_shift($conditions) . " ) ";
                foreach ($conditions as $value) {
                    $value = $this->adapter()->escapeString($value);
                    $condition = preg_replace('|\?|', $value, $condition, 1);
                }
            }  else {
                /* associative array
                 * Example:
                 * ->where(array('id'=>array('1','2'), 'name' =>'John') )
                 */
                $condition = " ( ";
                $w = array();
                foreach ($conditions as $key => $value) {
                    $key = $key;
                    $k = explode('.', $key);
                    $f = ( count($k) == 2 ) ? $this->apostrophe($k[0]) . '.' . $k[1] : $this->apostrophe($this->table()) . '.' . $k[0] ;
                    if ( count($k) == 2 ) $this->_tables_for_join[$k[0]] = $k[0];
                    if (is_array($value)) {
                        $w[] = $f . ' IN ( ' . join(", ", $value) . ' )';
                    } else {
                        $w[] = $f . ' = ' . $this->adapter()->escapeString($value);
                    }
                }
                $condition = $condition . join(" AND ", $w) . " ) ";
            }
        }            
        /* Find join tables in conditions */
        preg_match_all("/([a-z_]+)\.[a-z_]+/", $condition, $match);
        if (isset($match[1][0]))
            $this->_tables_for_join[$match[1][0]] = $match[1][0];

        $this->where .= empty($this->where) ? $condition : " {$operator} " . $condition;
        
        return $this;
    }

    public function count($field=null, $as=null)
    {
    }

    public function limit($count)
    {
        $this->limit = (int) $count;
        return $this;
    }

    public function offset($count)
    {
        $this->offset = (int) $count; 
        return $this;
    }

    public function from($table)
    {
        $this->from = $this->unapostrophe($table);
        return $this;
    }

    public function group($args)
    {
    }

    public function order($args)
    {
        if (is_string($args)) {
            $this->order = $args;
        } elseif (is_array($args)) {
            if (!isset($args['field']) && !isset($args['order']))
                return $this;
            $field = $args['field'];
            $order = strtolower($args['order']) == 'desc' || strtolower($order['order']) == 'asc' ? $args['order'] : '';
            $this->order = "{$field} ${order}";
        }
        return $this;
    }

    public function having()
    {
    
    }

    public function joins($models)
    {
        if (is_array($models)) {
            $this->joins = array_merge($this->joins, $models);
        } else {
            $this->joins = array_merge($this->joins, array_map('trim', explode(',', $models)));
        }
        if ( !empty($this->joins) ) {
            $model = $this->class_name;
            foreach ($this->joins as $join) {
                if ( $type = \Lycan\Record\Associations::associationTypeFor($join, $model)) {
                    $association = "\\Lycan\\Record\\Associations\\".Inflect::classify($type);
                    $this->join_queries[] = $association::joinQuery($this, $join, $model,
                        isset($model::$$type[$join]) 
                        ? $model::$$type[$join] 
                        : array()
                    );
                }
            }
        }
        return $this;        
    }

    public function includes($models)
    {
        if (is_array($models)) {
            $this->includes = $models;
        } else {
            $this->includes = array_map('trim', explode(',', $models));
        }
        return $this;   
    }

    public function all()
    {
        $this->fetch_method = 'all';
        return $this->_fetch_data();
    }

    public function fetch()
    {
        $this->fetch_method = 'one';
        return $this->_fetch_data();
    }

    public function first()
    {
        $this->limit(1);
        return $this->_fetch_data();
    }

    public function last()
    {
        $class_name = $this->class_name;
        $this->limit(1);
        $this->order("{$class_name::$primary_key} DESC");
        return $this->_fetch_data();
    }

    public function compileUpdate($attributes)
    {
        if ( empty($this->where) )
            throw new BadMethodCallException('You must call `where` method before call compileUpdate method');
        $data = "";
        foreach ($attributes as $name=>$value) {
            $data .= $this->apostrophe($name) . " = " . $this->_prepare_value($value) . ", "; 
        }
        $data = substr($data, 0, -2);
        $this->query = "UPDATE {$this->table()} SET {$data} WHERE {$this->where}";
        return $this;
    }

    public function compileInsert($attributes)
    {
        $attributes = array_map(array($this,'_prepare_value'),$attributes);
        $keys = implode(', ', array_keys($attributes));
        $values = implode(', ', $attributes);
        $this->query = "INSERT INTO `{$this->table()}` ({$keys}) VALUES ({$values})";
        return $this; 
    }

    private function _prepare_value($value)
    {
        if (null === $value || "" === $value) {
            return "NULL";
        } elseif (!is_numeric($value)){
            return $this->quote($this->adapter()->escapeString($value));
        } else {
            return $value;
        }
    }

    public function compileStmtUpdate($attributes)
    {
        $data = ""; $binds = array(); $types = "";
        foreach ($attributes as $name=>$value) {
            $data = $this->apostrophe($name) . " = ?";
            $types .= is_float($value) ? 'd' : (is_int($value) ? 'i' : 's');
            $binds[] = $this->quote($this->adapter()->escapeString($value));
        }
        $this->bind_params = array_merge(array($types), $binds);
        $this->query = "UPDATE {$this->table()} SET {$data} WHERE {$this->where}";
        return $this;
    }

    private function _fetch_data()
    {
        $this->build_sql();
        $model = $this->class_name;
        
        $records = $this->adapter()->query($this);

        $collection = new \Lycan\Record\Collection($records);
        

        if ( !$collection->isEmpty() && !empty($this->includes) ) {
            // include extra queries for fetching associations
            foreach( $this->includes as $include ) {
                if ( $type = \Lycan\Record\Associations::associationTypeFor($include, 
                    $model)
                ) {
                    $association = "\\Lycan\\Record\\Associations\\".Inflect::classify($type);
                    $association::bindObjectsToCollection($collection, 
                        $include, $model,
                        isset($model::$$type[$include]) 
                        ? $model::$$type[$include] 
                        : array()
                    );
                }
            }
        }

        if ('one' == $this->fetch_method) {
            return $collection->first();
        } else {
            return $collection;
        }

        return $this;
    }

    protected function table()
    {
        return $this->from ?: $this->table;
    }

    protected function build_select()
    {
        if ( null === $this->select ) return;
        $args = $this->select;

        if (!is_array($args))
            $args = array_map('trim', explode(',', $args));

        $select = "";
        foreach ($args as $a) {
            $table = $this->table();
            $column = null;
            $b = explode(".", $a);
            if (count($b) == 2)
                list($table, $column) = $b;
            if (!isset($column))
                $column = $a;
            if (isset($table)) {
                $select .= $this->apostrophe(trim($table)) . ".";
                $this->_select_values[$this->unapostrophe($table)][] = trim($column);
            }
            $select .= $column . ", ";
        }
        $this->select = substr($select, 0, -2);
    }

    protected function build_sql()
    {
        $class = $this->class_name;

        if ( !empty($this->join_queries) ) $this->select("`{$class::$table}`.*");
        $this->build_select();

        $query = isset($this->select) ? "SELECT {$this->select}" : "SELECT *";
        if (isset($this->_sum))
            $query .= ", {$this->sum}";
        if (isset($this->_count))
            $query .= ", {$this->count}";
        $query .= " FROM {$this->apostrophe($this->table())}";
        if (!empty($this->join_queries))
            $query .= " " . implode(" ", $this->join_queries);
        if (isset($this->where))
            $query .= " WHERE {$this->where}";
        if (isset($this->group))
            $query .= " GROUP BY {$this->group}";
        if (isset($this->order))
            $query .= " ORDER BY {$this->order}";
        if (isset($this->having))
            $query .= " HAVING {$this->having}";
        if (isset($this->limit))
            $query .= " LIMIT {$this->offset},{$this->limit}";
        $this->query = $query;
        return $this->query;
    }

    protected function unapostrophe($string)
    {
        if ( substr_count($string, '`') == 2 ) 
            return str_replace('`', '', $string);

        return $string;
    }

    protected function apostrophe($string)
    {
        if ( substr_count($string, '`') == 2 ) return $string;

        return '`' . $string . '`';
    }

    protected function quote($string)
    {
        if ( substr_count($string, "'") == 2 ) return $string;

        return "'" . $string . "'";       
    }
}
