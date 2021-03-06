<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Mysql;

use Lycan\Support\Inflect;

class Query extends \Lycan\Record\Query
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

    public function where(array $conditions, $operator='AND')
    {
        if (is_array($conditions)) {
            /* Example:
             * <code>
             * Object::find()->where(array('id = ? and username = ?', '1', 'John') )
             * </code>
             */
            $array_keys = array_keys($conditions);
            if (reset($array_keys) === 0 &&
                end($array_keys) === count($conditions) - 1 &&
                !is_array(end($conditions))) 
            {
                $condition = " ( " . array_shift($conditions) . " ) ";
                foreach ($conditions as $value) {
                    $value = $this->quote($this->escapeString($value));
                    $condition = preg_replace('|\?|', $value, $condition, 1);
                }
            }  else {
                /* associative array
                 * Example:
                 * <code>
                 * Object::find()->where(array('id'=>array('1','2'), 'name' =>'John') )
                 * </code>
                 */
                $condition = " ( ";
                $w = array();
                foreach ($conditions as $key => $value) {
                    $k = explode('.', $key);
                    $f = ( count($k) == 2 ) ? $this->apostrophe($k[0]) . '.' . $k[1] : $this->apostrophe($this->table()) . '.' . $k[0] ;
                    if ( count($k) == 2 ) $this->_tables_for_join[$k[0]] = $k[0];
                    if (is_array($value)) {
                        
                        array_walk($value,function(&$value, $key){ 
                            $value = "'$value'"; 
                        });

                        $w[] = $f . ' IN ( ' . join(", ", $value) . ' )';
                    } else {
                        $w[] = $f . ' = ' . $this->quote($this->escapeString($value));
                    }
                }
                $condition = $condition . join(" AND ", $w) . " )";
            }
        }            
        /* Find join tables in conditions */
        preg_match_all("/([a-z_]+)\.[a-z_]+/", $condition, $match);
        if (isset($match[1][0]))
            $this->_tables_for_join[$match[1][0]] = $match[1][0];

        $this->where .= empty($this->where) ? $condition : " {$operator}" . $condition;
        
        return $this;
    }

    public function andWhere(array $conditions)
    {
        return $this->where($conditions, 'AND');
    }

    public function orWhere(array $conditions)
    {
        return $this->where($conditions, 'OR');
    }

    public function count($field=null, $as=null)
    {
        $as = $as ?: "($field)_count";
        $this->count = null ===  $this->count 
            ? "COUNT($field) as $as"
            : $this->count . ", COUNT($field) as $as";

       return $this; 
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

    public function groupBy($args)
    {
        $this->group_by = $args;

        return $this;
    }

    public function orderBy($field, $order)
    {
        $this->order_by = null === $this->order_by 
            ? "{$field} ${order}" 
            : $this->order_by . "{$field} ${order}";

        return $this;
    }

    public function having()
    {
    
    }

    public function innerJoin($join_table, $primary_key, $foreign_key, $additional = null)
    {
        $this->_join("INNER", $join_table, $primary_key, $foreign_key, $additional);
        return $this;
    }

    public function leftJoin($join_table, $primary_key, $foreign_key, $additional = null)
    {
        $this->_join("LEFT", $join_table, $primary_key, $foreign_key, $additional);
        return $this;
    }

    private function _join($join_type, $join_table, $primary_key, $foreign_key, $additional = null)
    {
        list($pri_table, $pri_field) = explode('.', $primary_key);
        list($for_table, $for_field) = explode('.', $foreign_key);
        $this->join_queries[] = "{$join_type} JOIN `{$join_table}` ON (`{$pri_table}`.{$pri_field} = `{$for_table}`.{$for_field} {$additional}) "; 
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
                    $association = "\\Lycan\\Record\\Associations\\".$type;
                    $association::joinQuery($this, $join, $model,
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
        $this->fetch_method = 'one';
        return $this->_fetch_data();
    }

    public function last()
    {
        $class_name = $this->class_name;
        $this->limit(1);
        $this->orderBy("{$class_name::$primary_key}", "DESC");
        $this->fetch_method = 'one';
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

    public function compileInsert(array $attributes)
    {
        $attributes = array_map(array($this,'_prepare_value'),$attributes);
        $keys = implode(', ', array_keys($attributes));
        $values = implode(', ', $attributes);
        $this->query = "INSERT INTO {$this->table()} ({$keys}) VALUES ({$values})";
        return $this; 
    }

    public function compileDelete()
    {
        if ( empty($this->where) )
            throw new BadMethodCallException('You must call `where` method before call compileDelete method');

        $this->query = "DELETE FROM {$this->table()} WHERE {$this->where}";
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
/*
    public function compileStmtUpdate($attributes)
    {
        $data = ""; $binds = array(); $types = "";
        foreach ($attributes as $name=>$value) {
            $data = $this->apostrophe($name) . " = ?";
            $types .= is_float($value) ? 'd' : (is_int($value) ? 'i' : 's');
            $binds[] = $this->quote($this->escapeString($value));
        }
        $this->bind_params = array_merge(array($types), $binds);
        $this->query = "UPDATE {$this->table()} SET {$data} WHERE {$this->where}";
        return $this;
    }
*/
    private function _includes($include, $collection, $model)
    {
        if ( is_array($include) && !is_numeric(key($include)) ) {
            // we have chain associations to include
            foreach($include as $parent=>$v){
                // include the parent association first. 
                // return the included collection as $c. $collection already 
                // merged association records.
                $c = $this->_includes($parent, $collection, $model);
                // now if we have multiple includes, include them with parent 
                // class classify($k) to $c collection
                if ( is_array($v)) {
                    $class = \Lycan\Support\Inflect::classify($k);
                    $this->_includes($v, $c, $class);
                // if we have not multiply includes just include it with parent
                // classify($v) to $c collection
                } else {
                    $class = \Lycan\Support\Inflect::classify($v);
                    $this->_includes($v, $c, $class);
                }
            }
        } elseif ( is_array($include) && is_numeric(key($include)) ) {
            // just regular includes. not chains.
            foreach ( $include as $i ) {
                $this->_includes($i, $collection, $model);
            }
            // here we make the merge of the associations to main $collection 
            // dependent association type
        } else {
            if ( $type = \Lycan\Record\Associations::associationTypeFor($include, $model)
            ) {
                $association = "\\Lycan\\Record\\Associations\\".Inflect::classify($type);
                $options = $model::$$type;
                return $association::bindObjectsToCollection($collection, 
                    $include, $model,
                    isset($options[$include]) 
                    ? $options[$include] 
                    : array()
                );
            }
        }
    }

    private function _fetch_data()
    {
        $this->build_sql();
        $model = $this->class_name;
        
        $records = $this->adapter()->execute($this);

        #$collection = new \Lycan\Record\Collection($records, $model);
        $result = new ResultIterator($records);
        $collection = new \Lycan\Record\Collection($result, $model);
        
        if ( !$collection->isEmpty() && !empty($this->includes) ) {
            // include extra queries for fetching associations
            foreach( $this->includes as $k=>$include ) {
                if ( !is_numeric($k) ) {
                    // chain association detected so call _includes method 
                    // including $k
                    $this->_includes(array($k => $include), $collection,$model);
                } else {
                    $this->_includes($include, $collection,$model);
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
            $args = array_unique(array_map('trim', explode(',', $args)));
        
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
        #if (null != $this->query) return $this->query;

        $class = $this->class_name;

        if ( !empty($this->join_queries) ) $this->select("`{$class::$table}`.*");
        $this->build_select();

        $query = isset($this->select) ? "SELECT {$this->select}" : "SELECT *";
        if (isset($this->_sum))
            $query .= ", {$this->sum}";
        if (isset($this->count))
            $query .= ", {$this->count}";
        $query .= " FROM {$this->apostrophe($this->table())}";
        if (!empty($this->join_queries))
            $query .= " " . implode(" ", $this->join_queries);
        if (isset($this->where))
            $query .= " WHERE{$this->where}";
        if (isset($this->group_by))
            $query .= " GROUP BY {$this->group_by}";
        if (isset($this->order_by))
            $query .= " ORDER BY {$this->order_by}";
        if (isset($this->having))
            $query .= " HAVING {$this->having}";
        if (isset($this->limit))
            $query .= " LIMIT {$this->offset},{$this->limit}";
        $this->query = $query;
        return $this->query;
    }

    /**
     * Escapes a string to perform a safe sql query
     *
     * @param string $string the string to escape
     * @access public
     * @abstract
     *
     * @return string the escaped string
     */
    public function escapeString($string)
    {
        return $this->adapter()->escapeString($string);
    }

    public function __toString()
    {
        return $this->toSql();
    }

    public function toSql()
    {
        return null == $this->query ? $this->build_sql() : $this->query;
    }

    protected function unapostrophe($string)
    {
        return $this->adapter()->unapostrophe($string);
    }

    protected function apostrophe($string)
    {
        return $this->adapter()->apostrophe($string);
    }

    protected function quote($string)
    {
        return $this->adapter()->quote($string);
    }
}
