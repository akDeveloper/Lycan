<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Interfaces;

interface Adapter
{
    public function connect();

    public function disconnect();

    public function getConnection(); 

}
