<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

abstract class Persistence 
{ 
    public static $columns = array();

    public static $table;

    public static $primary_key;
    
    protected $attributes=array();

    protected $new_record=true;

    protected $destroyed=false;

    protected $readonly=false;

    public function __construct($attributes=array(), $options=array())
    {
        
        $this->new_record = isset($options['new_record']) ? $options['new_record'] : true;
        $options['new_record'] = $this->new_record;
        $this->attributes = Attributes::initialize(static::$columns, get_called_class(), $options);

        if ($attributes)
            $this->attributes->assign($attributes);
    
    }

    public static function initWith($attributes, $options=array())
    {
        $options['new_record'] = false;
        return new static($attributes, $options);
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
        return $this->_create_or_update();
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
    
    }

    protected function id()
    {
        return $this->attributes[static::$primary_key];
    }

    protected function setId($id)
    {
        $this->attributes[static::$primary_key] = $id;
    }

    private function _create_or_update()
    {
        $result = $this->new_record ? $this->_create() : $this->_update(); 
        return $result != false;
    }

    private function _update($attribute_names=null)
    {
        if ( null === $attribute_names ) $attribute_names = $this->attributes->keys();
        $attributes_with_values =  $this->attributes->attributesValues(false, false, $attribute_names);
        if ( empty($attributes_with_values) ) return 1;

        $query = static::find()->where(array(static::$primary_key => $this->id()))->compileUpdate($attributes_with_values);
        return static::$adapter->query($query);
    }

    private function _create()
    {
        $attributes_with_values =  $this->attributes->attributesValues(!is_null($this->id()));

        $query  = static::$adapter->getQuery(get_called_class())->compileInsert($attributes_with_values);
        $new_id = static::$adapter->insert($query);

        if (static::$primary_key)
            $this->id() ?: $this->setId($new_id);

        $this->new_record = false;
        $this->attributes->reload(false); 
        return $this->id();
    }

}
