<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

abstract class Persistence 
{
    protected $attributes=array();

    protected $new_record=true;

    public function __construct($attributes=array(), $new_record=true)
    {
        $this->new_record = $new_record;

    }

    public function decrement($attribute, $by=1)
    {
    
    }

    public function increment($attribute, $by=1) 
    {
    
    }

    public function delete()
    {
    
    }

    public function destroy()
    {
    
    }

    public function isNewRecord()
    {
    
    }

    public function isPersisted()
    {
    
    }

    public function isDestroyed()
    {
    
    }

    public function reload()
    {
    
    }

    public function toggle($attribute) 
    {
    
    }

    public function touch($attribute)
    {

    }

    public function save($validate=true)
    {
    
    }

    public function updateAttribute($name, $value)
    {
    
    }

    public function updateAttributes($attributes, $options=array())
    {
    
    }
    
    public function updateColumn($name, $value)
    {
    
    }

}
