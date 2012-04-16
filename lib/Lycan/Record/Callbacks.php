<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

class Callbacks implements Interfaces\Callbacks
{
    private static $_reflection_properties=array();

    public static $CALLBACKS = array(
        'after_initialize', 'after_find', 'after_touch', 'before_validation', 'after_validation',
        'before_save', 'around_save', 'after_save', 'before_create', 'around_create',
        'after_create', 'before_update', 'around_update', 'after_update',
        'before_destroy', 'around_destroy', 'after_destroy', 'after_commit', 'after_rollback'       
    );
    
    protected static function reflection_properties($model)
    {
		self::$_reflection_properties = array();
        $ref = new \ReflectionClass('\Lycan\Record\Model');
        $def_prop = $ref->getDefaultProperties();
        
        foreach( self::$CALLBACKS as $callback ) {
            if (array_key_exists($callback, $def_prop)) {
                if ( !isset(self::$_reflection_properties[$callback]) ) self::$_reflection_properties[$callback] = array();

                self::$_reflection_properties[$callback] = array_merge(self::$_reflection_properties[$callback], $def_prop[$callback]);
                
                if ( isset($model->$callback) && $model->$callback != $def_prop[$callback])
                    self::$_reflection_properties[$callback] = array_merge(self::$_reflection_properties[$callback], $model->$callback);
            } else {
                if ( isset($model->$callback) && !empty($model->$callback)) {
                    if ( !isset(self::$_reflection_properties[$callback]) ) self::$_reflection_properties[$callback] = array();
                    self::$_reflection_properties[$callback] = array_merge(self::$_reflection_properties[$callback], $model->$callback);
                }
            }
        }
    }

    protected static function perform_callback_for($kind, $chain, $model)
    {
        $obs = $norm = true;
        $callback_methods = array("{$chain}_{$kind}");
        if ($kind == 'update' || $kind == 'create') {
            $callback_methods[] = "{$chain}_save";
        }
        
        foreach( $callback_methods as $callback ) {
            if (!in_array($callback, self::$CALLBACKS)) continue;
            
            //Call model callback method
            if ( array_key_exists($callback, self::$_reflection_properties)) {
                foreach (self::$_reflection_properties[$callback] as $method) {
                    $norm = $model->$method();
                    if ( false === $norm ) break;
                }
            }
            
            //Call observer callbacks if exist
            if ( $model instanceof \SplSubject )
                $obs = $model->notifySubject($callback);
            if ( $obs !==false && $norm !== false ) 
                continue;
            else
                return false;
        }
        return true;
    }  

    public static function run_callbacks($kind, $model, $block=null)
    {
        self::reflection_properties($model);
        $res = self::perform_callback_for($kind, 'before', $model);
        $res = $res !== false ? $block() : false;
        return $res !== false ? self::perform_callback_for($kind, 'after', $model) : false;
    }
} 
