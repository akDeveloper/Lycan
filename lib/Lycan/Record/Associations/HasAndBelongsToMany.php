<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

use Lycan\Support\Inflect;

class HasAndBelongsToMany extends \Lycan\Record\Associations\Collection
{
    protected $association_foreign_key;
    protected $join_table;

    protected static function set_options($name, $model, $options)
    {
        list($association, $foreign_key, $primary_key) = parent::set_options($name, $model, $options);
        
        $association_foreign_key = self::association_foreign_key($association, $options);
        
        return array($association, $foreign_key, $primary_key, $association_foreign_key);
    }

    protected static function foreign_key($name, $model, $options)
    {
        return isset($options['foreign_key'])
            ? $options['foreign_key']
            : Inflect::singularize($model::$table) . "_" . $model::$primary_key;
    }

    protected static function association_foreign_key($association, $options)
    {
        return isset($options['association_foreign_key'])
            ? $options['association_foreign_key']
            : Inflect::singularize($association::$table) . "_" . $association::$primary_key;
    }

    public static function bindObjectsToCollection($collection, $name, $model, $options)
    {

        list($class, $foreign_key, $primary_key, $association_foreign_key) = self::set_options($name, $model, $options);

        $primary_key_values = array_unique($collection->toArray($primary_key));

        $join_table = self::_get_join_table($model, $class, $options);
        $table = $class::$table;
        $hbtm = $class::find()
            ->select("`{$table}`.*, `{$join_table}`.{$foreign_key} as the_parent_record_id")
            ->innerJoin($join_table, "{$join_table}.{$association_foreign_key}", "{$table}.{$primary_key}")
            ->where(array("$join_table.$foreign_key" => $primary_key_values))
            ->all();
        foreach ($collection as $value) {
            $select = $hbtm->select($value->$primary_key, 'the_parent_record_id');
            $value->$name->setWith($select);
        }
    }
    
    public static function joinQuery($query, $name, $model, $options)
    {
        list($class, $foreign_key, $primary_key, $association_foreign_key) = self::set_options($name, $model, $options);
        
        $join_table = self::_get_join_table($model, $class, $options);
        $table = $model::$table;
        $query->innerJoin($join_table, "{$join_table}.{$foreign_key}", "{$table}.{$primary_key}");
        $query->innerJoin($class::$table, "{$join_table}.{$association_foreign_key}", "{$class::$table}.{$primary_key}");
    }

    private static function _get_join_table($model, $association, $options)
    {
        if (isset($options['join_table'])) {
            $join_table = $options['join_table'];
        } else {
            /**
             * Sort tables alphabetical so can retrieve middle table.
             * Middle table does not need to be a Model object if it has not a primary key.
             */
            $table_arrays = array($model::$table, $association::$table);
            sort($table_arrays);
            $join_table = implode('_', $table_arrays);
        }
        return $join_table;       
    }

    public function __construct($name, $model, $options)
    {
        parent::__construct($name, $model, $options);
     
        $this->primary_key_value($model);
     
        list($this->association, $this->foreign_key, $this->primary_key, $this->association_foreign_key) = self::set_options($name, $this->model, $options);

        $this->join_table = self::_get_join_table($this->model, $this->association, $this->options);
    }

    protected function add_with_offset($object, $offset=null, $adapter=null)
    {
        $association = $this->association;

        if (!($object instanceof $association))
            throw new \InvalidArgumentException("Invalid type ".gettype($object).". Expected $association class instance");

        $ids = $this->all()->toArray($association::$primary_key);

        $adapter = $adapter ?: $association::getAdapter();

        if (!in_array($object->{$association::$primary_key}, $ids)) {

            $attributes = array(
                $this->association_foreign_key => $object->{$association::$primary_key},
                $this->foreign_key => $this->primary_key_value
            );

            $query = $adapter
                ->getQuery($this->association)
                ->from($this->join_table)
                ->compileInsert($attributes);
            $adapter->insert($query);

            $this->all()->offsetSet($offset, $object);
        }
    }

    public function delete_with_offset($object)
    {
        $association = $this->association;

        if (!($object instanceof $association))
            throw new \InvalidArgumentException("Invalid type ".gettype($object).". Expected $association class instance"); 

        
        $adapter = $association::getAdapter();
        
        $query = $adapter
            ->getQuery($this->association)
            ->from($this->join_table)
            ->where(array(
                $this->association_foreign_key=>$object->{$association::$primary_key}, 
                $this->foreign_key => $this->primary_key_value
            ))->compileDelete();
        $adapter->query($query);

        $this->all()->delete($object->{$association::$primary_key}, $association::$primary_key);

    }
    
    private function _batch_delete($to_delete)
    {
        $association = $this->association;
        $adapter = $association::getAdapter();
        
        $query = $adapter
            ->getQuery($association)
            ->from($this->join_table)
            ->where(array(
                $this->association_foreign_key=>$to_delete, 
                $this->foreign_key => $this->primary_key_value
            ))->compileDelete();
        
        $adapter->query($query);
        
        foreach ($to_delete as $id) {
            $this->all()->delete($id, $association::$primary_key);
        }
    }
    
    public function set(\Lycan\Record\Collection $collection)
    {
        $association = $this->association;

        $ids = $this->all()->toArray($association::$primary_key);
        $new_ids = $collection->toArray($association::$primary_key);

        $to_add = array_diff($new_ids, $ids);
        $to_delete = array_diff($ids, $new_ids);
        
        if (empty($to_add) && empty($to_delete)) return;

        if (!empty($to_add)) {

            $adapter = $association::getAdapter();
            
            foreach ($collection as $v) {
                if ( in_array($v->{$association::$primary_key}, $to_add) ) {
                    $this->add_with_offset($v, null, $adapter);
                }
            }
        }

        if (!empty($to_delete))
            $this->_batch_delete($to_delete);
    }

    /**
     * @params boolean $force_reload
     *
     * @return \Lycan\Record\Query a query instance
     */

    public function find($force_reload=false)
    {
        if ((null == $this->result_set && null != $this->primary_key_value) 
            || $force_reload || $this->result_set instanceof \Lycan\Record\Collection
        ) {
            $association = $this->association;
            $model = $this->model;
            
            $join_table = self::_get_join_table($this->model, $association, $this->options);

            $this->result_set = $association::find()
                ->innerJoin($join_table, "{$join_table}.{$this->association_foreign_key}", "{$association::$table}.{$association::$primary_key}")
                ->where(array($join_table . "." . $this->foreign_key => $this->primary_key_value));
        }
        
        return $this->result_set; 
    }
    
    protected function primary_key_value($model)
    {
        $this->primary_key_value = $model->{$this->primary_key};
    }
}

