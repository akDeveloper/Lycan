<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Interfaces;

interface Callbacks
{
    public static function run_callbacks($kind, $model, $block=null);
}
