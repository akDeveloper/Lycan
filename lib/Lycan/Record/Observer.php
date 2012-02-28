<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

abstract class Observer implements \SplObserver
{

    final public function update(\SplSubject $subject)
    {
    }

    final public function updateSubject(\SplSubject $subject, $method)
    {
        if ( method_exists($this, $method) )
            return $this->$method($subject);
    }
}

