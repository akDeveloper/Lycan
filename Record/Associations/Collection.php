<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

use Lycan\Support\Inflect;

abstract class Collection extends \Lycan\Record\Associations implements Interfaces\Collection, \IteratorAggregate, \ArrayAccess
{

    public function setWith(\Lycan\Record\Collection $collection)
    {
        $this->result_set = $collection;
    }

    public function isEmpty()
    {
        return $this->all()->isEmpty();
    }

    public function size()
    {
        return $this->all()->count(); 
    }

    public function getIds() 
    {
        $association = $this->association;
        return $this->all()->toArray($association::$primary_key);
    }
    
    public function setIds(array $ids)
    {
    
    }

    public function clear()
    {
    }

    public function exists()
    {
    
    }

    /**
     * IteratorAggregate
     */
    public function getIterator()
    {
        return $this->all();
    }

    /**
     * ArrayAccess
     */
    public function offsetExists ( $offset ) 
    {
        return $this->all()->offsetExists($offset);
    }

    public function offsetGet ( $offset )
    {
        return $this->all()->offsetGet($offset); 
    }

    public function offsetSet ( $offset , $value )
    {
        $this->set($value, $offset);
    }

    public function offsetUnset ( $offset )
    {
        $object = $this[$offset];
        $this->delete($object, $offset);
    }
}
