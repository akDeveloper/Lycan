<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Validations\Validators;

class Acceptance extends \Lycan\Validations\Validators\Each
{
    public function __construct($options)
    {
        $options = array_merge($options, array('allow_null'=>true, 'accept'=>1));
        parent::__construct($options);
    }

    public function validateEach($record, $attribute, $value)
    {
        if ($value != $this->options['accept']) {
            unset($this->options['allow_null']);
            unset($this->options['accept']);
            $record->errors()->add($attribute, ':accepted', $this->options);
        }
    }
}
