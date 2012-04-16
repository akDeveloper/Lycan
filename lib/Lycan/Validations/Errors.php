<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Validations;

class Errors implements \Iterator, \Countable, \ArrayAccess
{
    protected $messages=array();
    protected $base;

    public function __construct($base)
    {
        $this->base = $base;
        $this->messages = array();
    }

    public function clear()
    {
        $this->messages = array();
    }

    public function isEmpty()
    {
        return empty($this->messages);
    }

    public function add($attribute, $message=null, $options=array())
    {
        $message = $this->_normalize_message($attribute, $message, $options);
        !array_key_exists($attribute, $this->messages) ? $this->messages[$attribute]=array() : null;
        array_push($this->messages[$attribute], $message);
    }

    public function addOnEmpty($attributes, $options=array())
    {
        foreach ($attributes as $attribute) {
            $value = $this->base->$attribute;
            $is_empty = is_object($value) && method_exists($value, 'isEmpty') ? $value->isEmpty() : empty($value);
            if($is_empty) $this->add($attribute, ':empty', $options);
        }
    }

    public function addOnNull($attributes, $options=array())
    {
        foreach ($attributes as $attribute) {
            $value = $this->base->$attribute;
            $is_null = is_object($value) && method_exists($value, 'isNull') ? $value->isNull() : is_null($value);
            if($is_null) $this->add($attribute, ':null', $options);
        }
    }

    private function _normalize_message($attribute, $message, $options)
    {
        $message = $message ?: ':invalid';

        if ( 0 === strpos($message, ':') ) {
            return $this->generateMessage($attribute, $message, $options);
        } elseif (is_callable($message)) {
            return $message();
        } else {
            return $message;
        }
    }

    public function generateMessage($attribute, $type=':invalid', $options=array())
    {
        // TODO: i18n
        $message = null; 
        if (isset($options['message'])) {
            if (0 === strpos($options['message'], ':'))
                $type = $options['message'];
            else
                $message = $options['message'];
        }

        return $message ?: $type;
    }

    public function toArray()
    {
        return $this->messages;
    }
    // SPL - Countable functions
    // ----------------------------------------------

    /**
     * Get a count of all the records in the result set
     */
    public function count()
    {
        return count($this->messages);
    }

    // ----------------------------------------------
    // SPL - Iterator functions
    // ----------------------------------------------
    public function current()
    {
        return current($this->messages);
    }

    public function key()
    {
        return key($this->messages);
    }

    public function next()
    {
        next($this->messages);
    }

    public function rewind()
    {
        reset($this->messages);
    }

    public function valid()
    {
        return (current($this->messages) !== false);
    }

    // ----------------------------------------------
    // SPL - ArrayAccess functions
    // ----------------------------------------------
    public function offsetExists($key)
    {
        return isset($this->messages[$key]);
    }

    public function offsetGet($key)
    {
        return $this->messages[$key];
    }

    public function offsetSet($key, $value)
    {
        if ($key === null) {
            return $this->messages[] = $value;
        } else {
            return $this->messages[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        unset($this->messages[$key]);
    }

    // ----------------------------------------------   
}
