<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

class Attributes implements \Iterator, \ArrayAccess, \Serializable, \Countable
{
    protected $class_name;

    protected $new_record;

    private $_storage;

    private $_old_values = array();

    public function __construct($class_name, $input=array(), $options=array())
    {
        $this->_storage = $input;
        $this->class_name = $class_name;
        if ( isset($options['new_record'])) $this->new_record =  $options['new_record'];
    }

    public static function initialize($columns, $class_name, $options=array())
    {
        $attributes = array();
        if (isset($options['new_record']) && $options['new_record'])
            $attributes = array_combine($columns, array_pad(array(), count($columns), null));

        return new self($class_name, $attributes, $options);
    }

    public function assign($new_attributes, $options=array())
    {

        foreach ( $new_attributes as $k=>$v ) {
            if ($this->columnForAttribute($k))
                $this->new_record 
                ? $this->set($k, $v)
                : $this->_storage[$k] = $v;
        }
    }

    public function reload($new_record=null) {
        if ( $this->new_record && false === $new_record ) {
            $this->_old_values = array();
        }
    }

    public function set($key, $value)
    {
        if ( null == $key) return;

        if (isset( $this->_old_values[$key]) 
            && $this->_old_values[$key] == $value) // Maybe === operator 
        {
            unset($this->_old_values[$key]); 

        } elseif( $this->_storage[$key] !== $value ) {
            $this->_old_values[$key] = $this->_storage[$key];
            $this->_storage[$key] = $value;
        }       
    }

    public function attributesValues($include_primary_key=true, $include_readonly_attributes=true, $attribute_names=null)
    {
        if (empty($this->_old_values))  return array();
        if ( null === $attribute_names ) $attribute_names = array_keys($this->_storage);

        $attrs = array();
        $class = $this->class_name;
        
        foreach ($attribute_names as $name) {
            $column = $this->columnForAttribute($name);
            if ( array_key_exists($column, $this->_old_values ) && $column 
                && ($include_primary_key || !($column == $class::$primary_key)))
            {
                $value = $this[$name];
                $attrs[$name] = $value;
            }
        }

        return $attrs;
    }

    public function columnForAttribute($name)
    {
        $class = $this->class_name;
        return in_array($name, $class::$columns) ? $name : false;
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
