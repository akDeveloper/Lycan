<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

class Attributes implements \Iterator, \ArrayAccess, \Serializable, \Countable
{

    private $_primary_key;
    
    private $_columns;

    private $_composers;

    private $_storage;

    private $_class_name;

    private $_old_values = array();

    public function __construct($columns, $class_name, $options=array())
    {
        $attributes = array();
        if (isset($options['new_record']) && $options['new_record'])
            $attributes = array_combine($columns, array_pad(array(), count($columns), null));

        $this->_primary_key = $class_name::$primary_key;
        $this->_composers = $class_name::$composed_of;
        $this->_class_name = $class_name;

        $this->_storage = $attributes;
        if (isset($options['new_record']) && $options['new_record'])
            $this->_old_values = $attributes;
        $this->_columns = $columns;
    }

    public function assign($new_attributes, $options=array())
    {
        foreach ( $new_attributes as $k=>$v ) {
            #if ($this->columnForAttribute($k))
            isset($options['new_record']) && $options['new_record']
            ? $this->set($k, $v)
            : $this->_storage[$k] = $v;
        }
    }

    public function reload() {
        $this->_old_values = array();
    }

    /**
     * TODO: Reserve function for getting attribute values as objects ex.
     * \DateTime object for date values
     * or type cast special values like boolean from integer to boolean
     * etc
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->_storage))
            return $this[$key];
        throw new \Exception("Undefined index: {$this->_class_name}::{$key}");
    }


    public function set($key, $value)
    {
        if ( null == $key) return;

        if (isset( $this->_old_values[$key]) 
            && $this->_old_values[$key] == $value) // Maybe === operator 
        {
            unset($this->_old_values[$key]); 

        } elseif( array_key_exists($key, $this->_storage) && $this->_storage[$key] !== $value ) {
            $this->_old_values[$key] = $this->_storage[$key];
            $this->_storage[$key] = $value;
        } 
    }

    public function attributesValues($include_primary_key=true, $include_readonly_attributes=true, $attribute_names=null)
    {

        if (empty($this->_old_values))  return array();

        if ( null === $attribute_names ) $attribute_names = array_keys($this->_storage);

        $attrs = array();
        
        foreach ($attribute_names as $name) {
            $column = $this->columnForAttribute($name);
            if ( array_key_exists($column, $this->_old_values ) && $column 
                && ($include_primary_key || !($column == $this->_primary_key)))
            {
                $value = $this[$name];
                $attrs[$name] = $value;
            }
        }

        return $attrs;
    }

    public function columnForAttribute($name)
    {
        return in_array($name, $this->_columns) ? $name : false;
    }

    public function keys()
    {
        return array_keys($this->_storage);
    }

    // SPL - Countable functions
    // ----------------------------------------------

    /**
     * Get a count of all the records in the result set
     */
    public function count()
    {
        return count($this->_storage);
    }

    // ----------------------------------------------
    // SPL - Iterator functions
    // ----------------------------------------------
    public function current()
    {
        return current($this->_storage);
    }

    public function key()
    {
        return key($this->_storage);
    }

    public function next()
    {
        next($this->_storage);
    }

    public function rewind()
    {
        reset($this->_storage);
    }

    public function valid()
    {
        return (current($this->_storage) !== FALSE);
    }

    // ----------------------------------------------
    // SPL - ArrayAccess functions
    // ----------------------------------------------
    public function offsetExists($key)
    {
        return isset($this->_storage[$key]);
    }

    public function offsetGet($key)
    {
        return $this->_storage[$key];
    }

    public function offsetSet($key, $value)
    {
        if ($key === null) {
            $this->_storage[] = $value;
        } else {
            $this->_storage[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        if (is_int($key)) {
            array_splice($this->_storage, $key, 1);
        } else {
            unset($this->_storage[$key]);
        }
    }

    // ----------------------------------------------
    // SPL - Serializable functions
    // ----------------------------------------------
    public function serialize()
    {
        return serialize($this->_storage);
    }

    public function unserialize($serialized)
    {
        $this->_storage = unserialize($serialized);
    }
    // ----------------------------------------------
}
