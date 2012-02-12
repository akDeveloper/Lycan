<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Support;

class Callbacks extends Callbacks\Base
{
    private $_reflection_properties=array();

    public static $CALLBACKS = array(
        'after_initialize', 'after_find', 'after_touch', 'before_validation', 'after_validation',
        'before_save', 'around_save', 'after_save', 'before_create', 'around_create',
        'after_create', 'before_update', 'around_update', 'after_update',
        'before_destroy', 'around_destroy', 'after_destroy', 'after_commit', 'after_rollback'       
    );


    protected function reflection_properties()
    {
        if (empty($this->_reflection_properties)) {
            $ref = new \ReflectionClass('\Lycan\Record\Model');
            $def_prop = $ref->getDefaultProperties();
            
            foreach( self::$CALLBACKS as $callback ) {
                if (array_key_exists($callback, $def_prop)) {
                    if ( !isset($this->_reflection_properties[$callback]) ) $this->_reflection_properties[$callback] = array();

                    $this->_reflection_properties[$callback] = array_merge($this->_reflection_properties[$callback], $def_prop[$callback]);
                    
                    if ( isset($this->$callback) && $this->$callback != $def_prop[$callback])
                        $this->_reflection_properties[$callback] = array_merge($this->_reflection_properties[$callback], $this->$callback);
                }
            }
        }
        return $this->_reflection_properties;
    }

    protected function perform_callback_for($kind, $chain)
    {
        $obs = $norm = true;
        $callback_methods = array("{$chain}_{$kind}");
        if ($kind == 'update') {
            $callback_methods[] = "{$chain}_save";
        }
        foreach( $callback_methods as $callback ) {
            if (!in_array($callback, self::$CALLBACKS)) continue;
            
            //Call model callback method
            if ( array_key_exists($callback, $this->_reflection_properties)) {
                foreach ($this->_reflection_properties[$callback] as $method) {
                    $norm = $this->$method();
                    if ( false === $norm ) break;
                }
            }
            
            //Call observer callbacks if exist
            if ( $this instanceof \SplSubject )
                $obs = $this->notifySubject($callback);
            if ( $obs !==false && $norm !== false ) 
                continue;
            else
                return false;
        }
        return true;
    }

}
