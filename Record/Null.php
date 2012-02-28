<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

class Null extends Model
{
    public static $columns=array(null);

    public function __get($name)
    {
        return new self();
    }

    public function __toString()
    {
        return '';
    }
}

