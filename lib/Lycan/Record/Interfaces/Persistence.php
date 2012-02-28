<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Interfaces;

interface Persistence
{

    public function isNewRecord();
    
    public function isPersisted();

    public function isDestroyed();

    public function reload();

    public function toggle($attribute);
    
    public function touch($attribute);

    public function save();

    public function updateAttribute($name, $value);

    public function updateAttributes($attributes, $options=array());
    
    public function updateColumn($name, $value);

    public function decrement($attribute, $by=1);

    public function increment($attribute, $by=1);
    
    public function delete();
        
    public function destroy();
    
    public function update($attribute_names=null);
    
    public function create();
}
