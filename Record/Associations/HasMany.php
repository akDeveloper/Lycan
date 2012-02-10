<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

use Lycan\Support\Inflect;

class HasMany extends \Lycan\Record\Associations implements \IteratorAggregate
{
    public function __construct($name, $model, $options)
    {
        parent::__construct($name, $model, $options);
        $this->primary_key_value($model);
    }

    public function all()
    {
        $find = $this->find();
        return $find instanceof \Lycan\Record\Collection ? $find : $find->all();
    }

    public function find()
    {
        if ( null == $this->result_set && null != $this->primary_key_value) {
            $association = $this->association;
            $this->result_set = $association::find()
                ->where(array($this->foreign_key => $this->primary_key_value));
        }
        return $this->result_set; 
    }

    public function set(\Lycan\Record\Collection $collection)
    {
        $this->result_set = $collection;
    }
    
    public function getIterator()
    {
        return $this->all();
    }
    
    public static function bindObjectsToCollection($collection, $name, $model, $options)
    {
        // Setup options
        list($class, $foreign_key, $primary_key) = self::set_options($name, $model, $options); 
        
        $primary_key_values = array_unique($collection->toArray($primary_key));
        
        $has_many = $class::find()
            ->where(array($foreign_key => $primary_key_values))
            ->all();
        foreach ( $collection as $value ) {
            $select = $has_many->select($value->$primary_key, $foreign_key);
            $value->$name = $select;
        } 
    }

    protected static function foreign_key($name, $model, $options)
    {
        return isset($options['foreign_key'])
            ? $options['foreign_key']
            : Inflect::singularize($model::$table) . "_" . $model::$primary_key;
    }

    protected function primary_key_value($model)
    {
        $this->primary_key_value = $model->{$this->primary_key};
    }
}
