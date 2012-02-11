<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

class Persistence implements iPersistence 
{

    protected $class;
    
    protected $new_record=true;

    protected $destroyed=false;

    protected $readonly=false;

    public $attributes=array();
    
    public function __construct($class, $attributes=array(), $options=array())
    {
        $this->class = $class;
        $this->new_record = isset($options['new_record']) ? $options['new_record'] : true;
        $options['new_record'] = $this->new_record;
        $this->attributes = new Attributes($class::$columns, $class, $options);

        if ($attributes)
            $this->attributes->assign($attributes, array('new_record'=>$this->new_record));
    
    }

    public function isNewRecord()
    {
        return $this->new_record;
    }

    public function isPersisted()
    {
        return !($this->isNewRecord() || $this->isDestroyed()); 
    }

    public function isDestroyed()
    {
        return $this->destroyed;
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

    public function save()
    {
        return $this->create_or_update();
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
        $this->destroyed = true;
    }

    protected function id()
    {
        $class = $this->class;
        return $this->attributes[$class::$primary_key];
    }

    protected function setId($id)
    {
        $class = $this->class;
        $this->attributes[$class::$primary_key] = $id;
    }

    protected function create_or_update()
    {
        $result = $this->new_record ? $this->create() : $this->update();
        return $result != false;
    }

    public function update($attribute_names=null)
    {
        $class = $this->class;
        if ( null === $attribute_names ) $attribute_names = $this->attributes->keys();
        $attributes_with_values =  $this->attributes->attributesValues(false, false, $attribute_names);
        if ( empty($attributes_with_values) ) return 1;
        
        $query = $class::find()->where(array($class::$primary_key => $this->id()))->compileUpdate($attributes_with_values);
        $res = $class::getAdapter()->query($query);
        $this->attributes->reload(); 
        return $res;
    }

    public function create()
    {
        $class = $this->class;
        $attributes_with_values =  $this->attributes->attributesValues(!is_null($this->id()));

        $query  = $class::getAdapter()->getQuery($class)->compileInsert($attributes_with_values);
        $new_id = $class::getAdapter()->insert($query);

        if ($class::$primary_key)
            $this->id() ?: $this->setId($new_id);

        $this->new_record = false;
        $this->attributes->reload(); 
        return $this->id();
    }

}
