<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

class Filter extends \FilterIterator implements \ArrayAccess, \Countable
{
    protected $search_value;
    protected $field_value;

    private $_count=0;

    public function __construct(\Iterator $iterator, $search_value, $field_value)
    {
        parent::__construct($iterator);
        $this->search_value = $search_value;
        $this->field_value = $field_value;
    }

    public function accept()
    {
        $item = $this->getInnerIterator()->current();
        $value = $this->field_value;
        if ($this->search_value == $item->$value){
            $this->_count++;
            return true;
        }
        return false;
    }

    public function offsetExists($key)
    {
        return $this->getInnerIterator()->offsetExists($key);
    }

    public function offsetGet($key)
    {
        return $this->getInnerIterator()->offsetGet($key);
    }

    public function offsetSet($key, $value)
    {
        return $this->getInnerIterator()->offsetSet($key, $value);
    }

    public function offsetUnset($key)
    {
        return $this->getInnerIterator()->offsetUnset($key);
    }

    public function count()
    {
        return $this->_count;
    }
}
