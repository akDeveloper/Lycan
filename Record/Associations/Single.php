<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

abstract class Single extends \Lycan\Record\Associations implements Interfaces\Single
{

    public function __call($method, $args)
    {
        return $this->magic_method_call($method, $args, $this->fetch());
    }

    public function setWith(\Lycan\Record\Model $associate)
    {
        $this->result_set = $associate;
    }

}
