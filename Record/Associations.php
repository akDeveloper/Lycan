<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

use Lycan\Support\Inflect;

abstract class Associations
{ 

    static $associations = array('belongsTo', 'hasMany', 'hasOne', 'hasAndBelongsToMany');

    protected $name;

    protected $model;

    protected $association;

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
        $this->primary_key();
        $this->foreign_key(); 
        
        $this->primary_key_value($model);
        $this->foreign_key_value($model);
        
        $this->association(); 
    }

    public function find()
    {
        
    }

    public function __get($attribute)
    {   
        $fetch = $this->fetch();
        return  $fetch ? $fetch->$attribute : null;
    }

    protected function primary_key()
    {
        $model = $this->model;
        $this->primary_key = isset($this->options['primary_key'])
            ? $this->options['primary_key']
            : $model::$primary_key;
    }
    protected function foreign_key()
    {
        $model = $this->model;
        $this->foreign_key = isset($this->options['foreign_key'])
            ? $this->options['foreign_key']
            : Inflect::underscore($model::$table) . "_" . $model::$primary_key;
    }

    protected function primary_key_value($model)
    {
        $this->primary_key_value = $model->{$this->primary_key};
    }

    protected function foreign_key_value($model)
    {
        $this->foreign_key_value = $model->{$this->foreign_key};
    }

    protected function association()
    {
        $this->association = isset($this->options['class_name'])
            ? $this->options['class_name']
            : Inflect::classify($this->name);
    }
}
