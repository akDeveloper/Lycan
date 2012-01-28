<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Support;

interface iCallbacks
{
    public function run_callbacks($kind, $args);

    public function destroy();

    public function touch($options=array());

    public function save($options=array());
}
