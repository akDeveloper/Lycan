<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Validations;

abstract class Validate
{
    protected $default_keys = array('if', 'on', 'allow_empty', 'allow_null');
    private $_errors;
        
    public function errors()
    {
        $this->_errors = $this->_errors ?: new Errors($this);
        return $this->_errors;
    }

    public function isValid()
    {
        $this->errors()->clear();
        $this->run_validations();
        return $this->errors()->isEmpty(); 
    }

    public function isInvalid()
    {
        return !$this->isValid(); 
    }

    final public function validates($attrs, array $validations, $defaults=array())
    {
        foreach ($validations as $key=>$options) {
            $validator = "\\Lycan\\Validations\\Validators\\" . $key;
            if (!class_exists($validator))
                throw new \Exception("Unknown validator: {$key}");

            $defaults = array_merge($defaults, $this->_parse_validates_options($options));
            $defaults['attributes'] = $attrs;
            $vtor = new $validator($defaults);
            $vtor->validate($this);
        }
    }

    public function readAttributeForValidation($attribute)
    {
        return isset($this->$attribute) ? $this->$attribute : null;
    }

    abstract protected function validations();
    
    abstract protected function run_validations();

    private function _parse_validates_options($options)
    {
        if (is_array($options)) {
            return $options;
        } elseif(is_bool($options)) {
            return array();
        } else {
            return array('with' => $options);
        }

    }

} 
