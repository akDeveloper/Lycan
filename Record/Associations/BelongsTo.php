<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

use Lycan\Support\Inflect;

class BelongsTo extends \Lycan\Record\Associations
{
    public function __construct($name, $model, $options)
    {
        parent::__construct($name, $model, $options);
    }

    public function find()
    {
        $association = $this->association;
        $this->result_set = $association::find()
            ->where(array($association::$primary_key => $this->foreign_key_value))
            ->fetch();       
    }

    public function get()
    {

    }

    public static function bindObjectsToCollection($collection, $name, $called_class, $options)
    {
        // Setup options
        // TODO Should call a set_options static method and share it in case we 
        // call an instance of association.
        $class = isset($options['class_name'])
            ? $options['class_name']
            : Inflect::classify($name);
        
        $foreign_key = isset($options['foreign_key'])
            ? $options['foreign_key']
            : Inflect::underscore($class) . "_" . $called_class::$primary_key;  
        
        $primary_key = isset($options['primary_key'])
            ? $this->options['primary_key']
            : $called_class::$primary_key;       
        
        $primary_key_values = array_unique($collection->toArray($foreign_key));
        $belongs_to = $class::find()
            ->where(array($class::$primary_key => $primary_key_values))
            ->all();
        foreach ( $collection as $value ) {
            $detect = $value->$foreign_key == null 
                ? null 
                : $belongs_to->detect($value->$foreign_key, $class::$primary_key);
            if ( null != $detect )
             $value->$name = $detect;
        }

        
    }

    public function set(\Lycan\Record\Model $associate)
    {
        $this->result_set = $associate; // BelongsTo has only one association
    }
    
    public function fetch()
    {
        if ( null == $this->result_set && null != $this->foreign_key_value) {
            $association = $this->association;
            $this->result_set = $association::find()
                ->where(array($association::$primary_key => $this->foreign_key_value))
                ->fetch();
        }
        return $this->result_set;
    }

    protected function foreign_key()
    {
        $model = $this->model;
        $this->foreign_key = isset($this->options['foreign_key'])
            ? $this->options['foreign_key']
            : Inflect::singularize($this->name) . "_" . $model::$primary_key;       
    }
}
