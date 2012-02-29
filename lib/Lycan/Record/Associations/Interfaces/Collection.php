<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations\Interfaces;

interface Collection
{

    public function build($attributes=array());

    public function create($attributes=array());
    
    public function set(\Lycan\Record\Collection $collection);
    
    public function getIds();
    
    public function setIds(array $ids);
    
    public function find($force_reload=false);
    
    public function delete($object);
    
    public function add($object);
    
    public function clear();

    public function isEmpty();

    public function size();

    public function exists();
}
