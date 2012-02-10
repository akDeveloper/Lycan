<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

use Lycan\Support\Inflect;

abstract class Associations
{ 

    static $associations = array('belongsTo', 'hasMany', 'hasOne', 'hasAndBelongsToMany');

    /**
     * The name of the association as called from the Model
     */
    protected $name;

    /**
     * The actual class of association from $options['class_name'] or from 
     * Inflect::classify($name)
     */
    protected $association;

    /**
     * The class name of the Model that called this association
     */
    protected $model;


    protected $options;

    protected $foreign_key;
    protected $primary_key;

    protected $foreign_key_value;
    protected $primary_key_value;

    protected $result_set;

    public static function build($type, $name, $model, $options=array())
    {
        switch ($type) {
            case 'belongsTo':
                if ( isset($options['polymorphic']) )
                    return new Associations\BelongsToPolymorphic($name, $model, $options);
                return new Associations\BelongsTo($name, $model, $options);
                break;
            case 'hasMany':
                if ( isset($options['through']) )
                    return new Associations\HasManyThrough($name, $model, $options);
                return new Associations\HasMany($name, $model, $options);
                break;
            case 'hasOne':
                 if ( isset($options['through']) )
                    return new Associations\HasOneThrough($name, $model, $options);
                return new Associations\HasOne($name, $model, $options);               
                break;
            case 'hasAndBelongsToMany':
                return new Associations\HasAndBelongsToMany(
                    $name, 
                    $model, 
                    $options
                );
                break;
        }
    }

    public static function associationTypeFor($name, $model)
    {
        foreach (self::$associations as $assoc) {
            if (empty($model::$$assoc)) continue;
            if (   in_array($name, $model::$$assoc) 
                || array_key_exists($name, $model::$$assoc)
            ) {
                return $assoc;
            }
        }
        return false;       
    }

    public static function hasAssociation($name, $model)
    {
        $instance = $model;
        $model = get_class($model);
        $type = self::associationTypeFor($name, $model);
        return self::build($type, $name, $instance, 
            isset($model::$$type[$name]) 
            ? $model::$$type[$name] 
            : array()
        );
        return false;
    }

    public function __construct($name, $model, $options)
    {
        $this->name = $name;
        $this->model = get_class($model);
        $this->options = $options; 
        list($this->association, $this->foreign_key, $this->primary_key) = self::set_options($name, $this->model, $options);
    }

    protected static function set_options($name, $model, $options)
    {
        $association = static::association($name, $options);
            
        $foreign_key = static::foreign_key($name, $model, $options); 

        $primary_key = static::primary_key($model, $options); 

        return array($association, $foreign_key, $primary_key);
    }

    public function find()
    {
        
    }

    public function __get($attribute)
    {   
        $fetch = $this->fetch();
        return  $fetch ? $fetch->$attribute : null;
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
}
