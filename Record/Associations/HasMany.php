<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

use Lycan\Support\Inflect;

class HasMany extends \Lycan\Record\Associations\Collection
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

    public function find($force_reload=false)
    {
        if ((null == $this->result_set && null != $this->primary_key_value) 
            || $force_reload
        ) {
            $association = $this->association;
            $this->result_set = $association::find()
                ->where(array($this->foreign_key => $this->primary_key_value));
        }
        return $this->result_set; 
    }

    public function set($value, $offset=null)
    {
        $association = $this->association;
        
        if (!($value instanceof $association) && !($value instanceof \Lycan\Record\Collection))
            throw new \InvalidArgumentException("Invalid object ".get_class($value).". Expected $association or \Lycan\Record\Collection");

        $ids = $this->all()->toArray($association::$primary_key);

        if ($value instanceof $association) {
            if (   null == $value->{$association::$primary_key}
                || !in_array($value->{$association::$primary_key})
            ) {
                $value->{$this->foreign_key} = $this->primary_key_value;
                //if ( $value->save() )
                    $this->all()->offsetSet($offset, $value); 
            }
        } elseif ($value instanceof \Lycan\Record\Model) {
            
        }

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
            $value->$name->setWith($select);
        } 
    }

    public static function joinQuery($query, $name, $model, $options)
    {
        list($class, $foreign_key, $primary_key) = self::set_options($name, $model, $options);
        
        $join_table = $class::$table;
        $table = $model::$table;
        return "INNER JOIN `$join_table` ON `$join_table`.{$foreign_key} = `$table`.$primary_key";        
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
