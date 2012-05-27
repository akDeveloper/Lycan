<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

class Collection implements \Iterator, \Countable, \ArrayAccess
{

    protected $results = array();
    protected $model;

    protected $caching=array();

    public function __construct($results = array(), $model=null)
    {
        $this->results = $results;
        $this->model = $model;
    }

    /**
     * Returns first result in set
     */
    public function first()
    {
        if (!empty($this->results)) {
            return $this->_get_item(reset($this->results),key($this->results));
        }

        return null;
    }

    /**
     * Returns last result in set
     */
    public function last()
    {
        if (!empty($this->results)) {
            return $this->_get_item(end($this->results), key($this->results));
        }

        return null;
    }

    public function isEmpty()
    {
        return empty($this->results);
    }

    public function map(\Closure $block) 
    {
        return new self(array_map($block, array_keys($this->results), $this->results));
    }

    public function each_with_index(\Closure $block) 
    {
        foreach ($this->results as $key => $value) {
            $block($key, $this->_get_item($value, $key));
        }
    }

    public function each(\Closure $block)
    {
        foreach ($this->results as $key => $value) {
            $block($this->_get_item($value, $key));
        }
    }

    /**
     * ResultSet to array using given key/value columns
     */
    public function toArray($keyColumn = null, $valueColumn = null)
    {
        // Both empty
        if (null === $keyColumn && null === $valueColumn) {
            $return = $this->results;

            // Key column name
        } elseif (null !== $keyColumn && null === $valueColumn) {
            $return = array();
            foreach ($this->results as $k=>$row) {
                if (array_key_exists($keyColumn, $row))
                    $return[$k] = $row[$keyColumn];
            }

            // Both key and value columns filled in
        } else {
            $return = array();
            foreach ($this->results as $row) {
                $return[$row[$keyColumn]] = $row[$valueColumn];
            }
        }

        return $return;
    }

    public function select($search_value, $field_value)
    {
        $return = array();
        
        $return = array_filter($this->results, function($row) use ($search_value, $field_value){
            return $search_value == $row[$field_value];
        });
        
        return new self(array_values($return));
    }

    public function detect($search_value, $field_value)
    {
        $return = array(null);
        $return = array_filter($this->results, function($row) use ($search_value, $field_value){
            return $search_value == $row[$field_value];
        });
        return $this->_get_item(current($return),key($return));
    }

    public function delete($search_value, $field_value)
    {
        $array = array();
        $array = array_filter($this->results, function($row) use ($search_value, $field_value){
            return $search_value == $row[$field_value];
        });
        if (!empty($array)) {
            $key = key($array);
            unset($this->results[$key]);
        }
    }

    public function toJson()
    {
        return json_encode($this->results);
    }

    // SPL - Countable functions
    // ----------------------------------------------

    /**
     * Get a count of all the records in the result set
     */
    public function count()
    {
        return count($this->results);
    }

    // ----------------------------------------------
    // SPL - Iterator functions
    // ----------------------------------------------
    public function current()
    {
        return $this->_get_item(current($this->results), key($this->results));
    }

    public function key()
    {
        return key($this->results);
    }

    public function next()
    {
        next($this->results);
    }

    public function rewind()
    {
        reset($this->results);
    }

    public function valid()
    {
        return (current($this->results) !== FALSE);
    }

    // ----------------------------------------------
    // SPL - ArrayAccess functions
    // ----------------------------------------------
    public function offsetExists($key)
    {
        return isset($this->results[$key]);
    }

    public function offsetGet($key)
    {
        return $this->_get_item($this->results[$key], $key);
    }

    public function offsetSet($key, $value)
    {
        if ($key === null) {
            return $this->results[] = $value->getAttributes();
        } else {
            $this->caching[$key] = $value;
            return $this->results[$key] = $value->getAttributes();
        }
    }

    public function offsetUnset($key)
    {
        unset($this->results[$key]);
        unset($this->caching[$key]);
    }

    // ----------------------------------------------
    
    private function _get_item($item, $key=null) 
    {
        if (array_key_exists($key, $this->caching))
            return $this->caching[$key];
        $model = $this->model;
        $this->caching[$key] = $model::initWith($item);
        return $this->caching[$key];
    }
}
