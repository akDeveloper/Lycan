<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

use Lycan\Support\Inflect;

class HasMany extends \Lycan\Record\Associations\Collection 
{

    public static function bindObjectsToCollection($collection, $name, $model, $options)
    {
        // Setup options
        list($class, $foreign_key, $primary_key) = self::set_options($name, $model, $options); 
        
        $primary_key_values = array_unique($collection->toArray($primary_key));
        
        $has_many = $class::find()
            ->where(array($foreign_key => $primary_key_values))
            ->all();
        foreach ($collection as $value) {
            $select = $has_many->select($value->$primary_key, $foreign_key);
            $value->$name->setWith($select);
        } 
    }

    public static function joinQuery($query, $name, $model, $options)
    {
        list($class, $foreign_key, $primary_key) = self::set_options($name, $model, $options);
        
        $join_table = $class::$table;
        $table = $model::$table;
        $query->innerJoin($join_table, "{$join_table}.{$foreign_key}", "{$table}.{$primary_key}");
    }

    protected static function foreign_key($name, $model, $options)
    {
        return isset($options['foreign_key'])
            ? $options['foreign_key']
            : Inflect::singularize($model::$table) . "_" . $model::$primary_key;
    }

    public function __construct($name, $model, $options)
    {
        parent::__construct($name, $model, $options);
        $this->primary_key_value($model);
    }

    protected function add_with_offset($object, $offset=null, $adapter=null)
    {
        $association = $this->association;
        
        if (!($object instanceof $association))
            throw new \InvalidArgumentException("Invalid type ".gettype($object).". Expected $association class instance"); 

        $ids = $this->all()->toArray($association::$primary_key);

        if (!in_array($object->{$association::$primary_key}, $ids)) {
            $object->{$this->foreign_key} = $this->primary_key_value;
            if ($object->save())
                $this->all()->offsetSet($offset, $object);
        } 
    }

    protected function delete_with_offset($object, $offset=null, $adapter=null)
    {
        $association = $this->association;

        if (!($object instanceof $association))
            throw new \InvalidArgumentException("Invalid type ".gettype($object).". Expected $association class instance");

        $adapter = $adapter ?: \Lycan\Record\Model::getAdapter();
        
        if( isset($this->options['dependent']) ) {
            switch ($this->options['dependent']) {
            case 'destory':
                $object->destroy();
                break;
            case 'delete':
                $object->destroy();
                break;
            default:
                $object->{$this->foreign_key} = null;
                $object->save();
                break;
            }
        } else {
            $object->{$this->foreign_key} = null;
            $object->save();
        }
        
        if (null === $offset) 
            $this->all()->delete($object->{$association::$primary_key}, $association::$primary_key);
        else
            $this->all()->offsetUnset($offset);
    }

    public function set(\Lycan\Record\Collection $collection)
    {
        $association = $this->association;
        
        $ids = $this->all()->toArray($association::$primary_key);
        $new_ids = $collection->toArray($association::$primary_key);
        
        $to_add = array_diff($new_ids, $ids);
        $to_delete = array_diff($ids, $new_ids);       
        
        $adapter = $association::getAdapter();

        $objects_to_add = array();
        foreach ($collection as $row) {
            if (in_array($row->{$association::$primary_key}, $to_add))
                $objects_to_add[] = $row;
        }

        $objects_to_add = array_filter($objects_to_add);
        foreach ($objects_to_add as $v) {
            $this->add_with_offset($v, null, $adapter);
        }
        
        $objects_to_delete = array();
        foreach ($collection as $row) {
            if (in_array($row->{$association::$primary_key}, $to_delete))
                $objects_to_delete[] = $row;
        }

        $objects_to_delete = array_filter($objects_to_delete);
        foreach ($objects_to_delete as $k=>$v) {
            $this->delete_with_offset($v, $k, $adapter); 
        }
        
    }

    public function find($force_reload=false)
    {
        if ((null == $this->result_set && null != $this->primary_key_value) 
            || $force_reload || $this->result_set instanceof \Lycan\Record\Collection
        ) {
            $association = $this->association;
            $this->result_set = $association::find()
                ->where(array($this->foreign_key => $this->primary_key_value));
        }
        return $this->result_set; 
    }
    
    protected function primary_key_value($model)
    {
        $this->primary_key_value = $model->{$this->primary_key};
    }
}
