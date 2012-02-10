<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

class Collection implements \Iterator, \Countable, \ArrayAccess
{

    protected $results = array();

    public function __construct($results = array())
    {
        $this->results = $results;
    }

    /**
     * Returns first result in set
     */
    public function first()
    {
        if (isset($this->results[0])) {
            return $this->results[0];
        }
        return null;
    }

    /**
     * Returns last result in set
     */
    public function last()
    {
        if (isset($this->results[$this->count() - 1])) {
            return $this->results[$this->count() - 1];
        }
        return null;
    }

    public function isEmpty()
    {
        return empty($this->results);
    }

    public function map( \Closure $block ) 
    {
        return new self( array_map( $block, array_keys($this->results), $this->results ) );
    }

    public function each_with_index( \Closure $block ) 
    {
        foreach( $this->results as $key => $value ){
            $block($key, $value);
        }
    }

    public function each( \Closure $block ) 
    {
        foreach( $this->results as $value ){
            $block($value);
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
            foreach ($this->results as $row) {
                if (($row->$keyColumn))
                    $return[] = $row->$keyColumn;
            }

            // Both key and valid columns filled in
        } else {
            $return = array();
            foreach ($this->results as $row) {
                $return[$row->$keyColumn] = $row->$valueColumn;
            }
        }

        return $return;
    }

    public function select($search_value, $field_value)
    {
        $return = array();
        foreach ($this->results as $row) {
            if ($search_value == $row->$field_value)
                $return[] = $row;
        }
        return new self($return);
    }

    public function detect($search_value, $field_value)
    {
        $return = null;
        foreach ($this->results as $row) {
            if ($search_value == $row->$field_value)
                return $row;
        }
        return $return;
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
        return current($this->results);
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
        return $this->results[$key];
    }

    public function offsetSet($key, $value)
    {
        if ($key === null) {
            return $this->results[] = $value;
        } else {
            return $this->results[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        if (is_int($key)) {
            array_splice($this->results, $key, 1);
        } else {
            unset($this->results[$key]);
        }
    }

    // ----------------------------------------------
}
