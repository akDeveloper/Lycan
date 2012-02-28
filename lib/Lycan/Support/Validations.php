<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Support;

class Validations extends Callbacks 
{

    public function save($options=array())
    {
        if ($this->perform_validations($options)) {
            return $this->persistence->save(); 
        } else {
            return false;
        } 
    }

    public function isValid($context=null)
    {
        return false;
    }

    protected function perform_validations($options=array()) 
    {
        $perform_validation = !isset($options['validate']) || $options['validate'] != false;
        return $perform_validation ? $this->isValid(isset($options['context']) ? $options['context'] : null) : true;
    }
} 
