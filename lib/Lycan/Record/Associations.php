<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

require_once __DIR__ . DS . 'Exceptions.php';

use Lycan\Support\Inflect;

abstract class Associations
{ 

    static $associations = array('belongsTo', 'hasMany', 'hasOne', 'hasAndBelongsToMany');

    /**
     * The name of the association as called from the Model
     *
     * @var string
     */
    protected $name;

    /**
     * The actual class of association from $options['class_name'] or from 
     * Inflect::classify($name)
     *
     * @var string
     */
    protected $association;

    /**
     * The class name of the Model that called this association
     *
     * @var string
     */
    protected $model;

    /**
     * The class instance of the Model that called this association
     *
     * @var \Lycan\Record\Model
     */
    protected $model_instane;

    protected $options;

    protected $foreign_key;
    protected $primary_key;

    protected $foreign_key_value;
    protected $primary_key_value;
  
    /**
     * When associate object is a new object and parent object calls save 
     * method, forces association to call its save method 
     * and set foreign key or join tables fields with appropriate values.
     *
     * @var boolean
     */
  
    protected $marked_for_save=false;
    
    /**
     * Handles model or collection objects from associations calls
     * 
     * @var mixed
     */
    protected $result_set;

    public static function associationTypeFor($name, $model)
    {
        foreach (self::$associations as $assoc) {
            if (empty($model::$$assoc)) continue;
            if (   in_array($name, $model::$$assoc) 
                || array_key_exists($name, $model::$$assoc)
            ) {
                $o = $model::$$assoc;
                $options = array_key_exists($name, $o) 
                    ? $o[$name] : array();
                if (!empty($options)) {
                    if ('belongsTo' == $assoc && isset($options['polymorphic'])) return 'BelongsToPolymorphic';
                    if ('hasMany' == $assoc && isset($options['through'])) return 'HasManyThrough';
                    if ('hasOne' == $assoc && isset($options['through'])) return 'HasOneThrough';
                    return Inflect::classify($assoc);
                }
                return Inflect::classify($assoc);
            }
        }
        return false;       
    }

    public static function buildAssociation($name, $model)
    {
        $instance = $model;
        $model = get_class($model);
        $type = self::associationTypeFor($name, $model);
        
        if (false == $type) return false;
        
        $association = "\\Lycan\\Record\\Associations\\" . $type;
        
        return new $association($name, $instance,
            isset($model::$$type[$name]) 
            ? $model::$$type[$name] 
            : array());
    }

    protected static function set_options($name, $model, $options)
    {
        $association = static::association($name, $options);
            
        $foreign_key = static::foreign_key($name, $model, $options); 

        $primary_key = static::primary_key($model, $options); 

        return array($association, $foreign_key, $primary_key);
    }


    public static function bindObjectsToCollection($collection, $name, $model, $options)
    {
        throw new \Exception(__FUNCTION__ . " must be implemented.");
    }
    
    public static function joinQuery($query, $name, $model, $options)
    {
        throw new \Exception(__FUNCTION__ . " must be implemented.");
    }

    protected static function primary_key($model, $options)
    {
        return isset($options['primary_key'])
            ? $options['primary_key']
            : $model::$primary_key;
    }

    protected static function association($name, $options)
    {
        return isset($options['class_name'])
            ? $options['class_name']
            : Inflect::classify($name);    
    }

    public function __construct($name, $model, $options)
    {
        $this->name = $name;
        $this->model = get_class($model);
        $this->model_instance = $model;
        $this->options = $options; 
        list($this->association, $this->foreign_key, $this->primary_key) = static::set_options($name, $this->model, $options);
    }

    public function needSave()
    {
        return $this->marked_for_save === true;
    }

    protected function magic_method_call($method, $args, $instance)
    {
        try {
            $method = new \ReflectionMethod($instance, $method);
            return $method->invokeArgs($instance, $args);
        } catch(\ReflectionException $e) {
           throw new InvalidMethodException(get_class($instance), $method);
        }
    }
}
