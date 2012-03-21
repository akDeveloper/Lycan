<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

use Lycan\Support\Inflect;

class BelongsToPolymorphic extends \Lycan\Record\Associations\BelongsTo
{

    public static function bindObjectsToCollection($collection, $name, $model, $options)
    {
        list($class, $foreign_key, $primary_key) = self::set_options($name, $model, $options);
        $poly_classes = array_unique($collection->toArray($name . '_type'));

        foreach ($poly_classes as $poly) {
            $types = $collection->select($poly, $name . "_type");
            $poly_ids = array_unique($types->toArray($name . "_id"));
            
            if (empty($poly_ids)) return;

            $poly_query = $poly::find()
                ->where(array($poly::$primary_key=>$poly_ids))
                ->all();
            foreach ($collection as $value) {
                $detect = $value->$foreign_key ==  null
                    ? null
                    : $poly_query->detect($value->$foreign_key, $primary_key);
                if ( null != $detect )
                    $value->$name->setWith($detect);
                else
                    $value->$name->setWith(new \Lycan\Record\Null());
            }
            #print_r($collection->last()->commentable->title);
        }
    }

    public static function joinQuery($query, $name, $model, $options)
    {
        
    }
    
    public function __construct($name, $model, $options)
    {
        parent::__construct($name, $model, $options);
        $this->association = $this->model_instance->{$this->name."_type"};
    }
    
    public function saveAssociation()
    {
        $associate = $this->fetch();
        $association = $this->association;
        $id = $association::$primary_key;
        $save = true;
        
        if ( $associate->isNewRecord() ) 
            $save = $associate->save();

        if ($save) {
            $this->model_instance->{$this->name . "_id"} = $associate->$id;
            $this->model_instance->{$this->name . "_type"} = get_class($associate);
            $this->marked_for_save = false;
        }
    }
    
    public function set(\Lycan\Record\Model $associate)
    {
        $association = $this->association;
        
        if ( null !== $associate->{$association::$primary_key} ) {
            $this->model_instance->{$this->name . "_id"} = $associate->{$association::$primary_key};
            $this->model_instance->{$this->name . "_type"} = get_class($associate);
        } else {
            $this->marked_for_save = true;
        }
            
        $this->result_set = $associate; 
    }   
}
