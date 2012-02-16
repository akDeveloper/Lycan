<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

use Lycan\Support\Inflect;

class HasOne extends \Lycan\Record\Associations\Single
{

    public static function bindObjectsToCollection($collection, $name, $model, $options)
    {
        // Setup options
        list($class, $foreign_key, $primary_key) = self::set_options($name, $model, $options); 

        $primary_key_values = array_unique($collection->toArray($primary_key));

        $has_one = $class::find()
            ->where(array($foreign_key => $primary_key_values))
            ->all();
        foreach ( $collection as $value ) {
            $detect = $has_one->detect($value->$primary_key, $foreign_key);
            if ( null != $detect )
                $value->$name->setWith($detect);
        }
        return $has_one;
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

    public function __construct($name, $model, $options)
    {
        parent::__construct($name, $model, $options);
        $this->primary_key_value($model);
    }

    public function build($attributes=array())
    {
    
    }

    public function create($attributes=array())
    {
    }

    public function saveAssociation()
    {
        $associate = $this->fetch();
        $association = $this->association;
        $id = $association::$primary_key;
        $save = true;

        $associate->{$this->foreign_key} = $this->model_instance->{$this->primary_key};
        $save = $associate->save();

        if ($save) 
            $this->marked_for_save = false;
        
    }
        
    public function set(\Lycan\Record\Model $associate)
    {
        $association = $this->association;
        
        $prev_associate = $this->fetch();
        if ( $prev_associate ) {
            if( isset($this->options['dependent']) ) {
                switch ($this->options['dependent']) {
                    case 'destory':
                        $prev_associate->destroy();
                        break;
                    case 'delete':
                        $prev_associate->destroy();
                        break;
                    default:
                        $prev_associate->{$this->foreign_key} = null;
                        $prev_associate->save();
                        break;
                }
            } else {
                $prev_associate->{$this->foreign_key} = null;
                $prev_associate->save();
            }
        }

        if ( null !== $associate->{$association::$primary_key} ) {
            $associate->{$this->foreign_key} = $this->primary_key_value;
            if ( $associate->save() ){
                $this->result_set = $associate;
                return $associate;
            } else {
                return false;
            }
        } else {
            $this->result_set = $associate;
            $this->marked_for_save = true;
            return $associate;
        }
            
    }

    public function fetch()
    {
        $find = $this->find();
        return $find instanceof \Lycan\Record\Query ? $find->fetch() : $find;
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

    protected function primary_key_value($model)
    {
        $this->primary_key_value = $model->{$this->primary_key};
    }
}
