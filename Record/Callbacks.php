<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

abstract class Callbacks extends \Lycan\Support\Callbacks implements \Lycan\Support\iCallbacks
{

    public function run_callbacks($kind, $args=null)
    {
        $res = $this->perform_callback_for($this, $this->persistence, $kind, 'before');
        $res = $res !== false ? $this->persistence->$kind($args) : false;
        return $res !== false ? $this->perform_callback_for($this, $this->persistence, $kind, 'after') : false;
    }

    public function destroy()
    {
    
    }

    public function touch($options=array())
    {
    
    }

    public function save($options=array())
    {
        return $this->create_or_update($options);
    }

    protected function create_or_update($options=array())
    {
        if ( !($this->persistence instanceof \Lycan\Record\iPersistence) )
            throw new \LogicException( get_class($this) . "::persistence property must implements \Lycan\Record\iPersistence interface");
        
        $result = $this->persistence->isNewRecord() ? $this->create() : $this->update();
        return $result != false;# ? $this->run_callbacks('save', $options) : false;
    }

    private function create()
    {
        return $this->run_callbacks('create');
    }

    public function update($options=null)
    {
        return $this->run_callbacks('update', $options);
    }
} 
