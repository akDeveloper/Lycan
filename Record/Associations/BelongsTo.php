<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

use Lycan\Support\Inflect;

class BelongsTo extends \Lycan\Record\Associations
{
    public function __construct($name, $model, $options)
    {
        parent::__construct($name, $model, $options);
        $this->foreign_key_value($model);
    }

    public function fetch()
    {
        $find = $this->find();
        return $find instanceof \Lycan\Record\Model ? $find : $find->fetch();
    }

    public function find()
    {
        if ( null == $this->result_set && null != $this->foreign_key_value) {
            $association = $this->association;
            $this->result_set = $association::find()
                ->where(array($association::$primary_key => $this->foreign_key_value));
        }
        return $this->result_set;
    }

    public function set(\Lycan\Record\Model $associate)
    {
        $this->result_set = $associate; // belongsto has only one association
    }

    public static function bindObjectsToCollection($collection, $name, $model, $options)
    {
        // Setup options
        list($class, $foreign_key, $primary_key) = self::set_options($name, $model, $options);
        
        $primary_key_values = array_unique($collection->toArray($foreign_key));
        if (empty($primary_key_values)) return;
        $belongs_to = $class::find()
            ->where(array($primary_key => $primary_key_values))
            ->all();
        foreach ( $collection as $value ) {
            $detect = $value->$foreign_key == null 
                ? null 
                : $belongs_to->detect($value->$foreign_key, $primary_key);
            if ( null != $detect )
                $value->$name = $detect;
        } 
    }

    public static function joinQuery($query, $name, $model, $options)
    {
        list($class, $foreign_key, $primary_key) = self::set_options($name, $model, $options);
        $join_table = $class::$table;
        $table = $model::$table;
        return "INNER JOIN `$join_table` ON `$join_table`.{$class::$primary_key} = `$table`.$foreign_key";
    } 

    protected static function foreign_key($name, $model, $options)
    {
        return isset($options['foreign_key'])
            ? $options['foreign_key']
            : Inflect::singularize($name) . "_" . $model::$primary_key;       
    }

    protected function foreign_key_value($model)
    {
        $this->foreign_key_value = $model->{$this->foreign_key};
    }
}
