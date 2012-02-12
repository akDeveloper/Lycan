<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

abstract class Single extends \Lycan\Record\Associations implements Interfaces\Single
{
    /**
     * When associate object is a new object and parent object calls save 
     * method, forces association to call its save method 
     * and set foreign key or join tables fields with appropriate values.
     *
     * @var boolean
     */
    protected $marked_for_save=false;

    public function __call($method, $args)
    {
        return $this->magic_method_call($method, $args, $this->fetch());
    }

    public function setWith(\Lycan\Record\Model $associate)
    {
        $this->result_set = $associate;
    }

}
