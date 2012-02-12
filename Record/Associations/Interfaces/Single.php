<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations\Interfaces;

interface Single
{
    public function build($attributes=array());

    public function create($attributes=array());

    public function set(\Lycan\Record\Model $associate);
}

