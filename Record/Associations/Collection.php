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
            //$this->all()->offsetSet( $offset , $value );
    }

    public function offsetUnset ( $offset )
    {
        $this->all()->offsetUnset( $offset );
    }
}
