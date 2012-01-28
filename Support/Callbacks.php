<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Support;

class Callbacks extends Callbacks\Base
{

    public static $CALLBACKS = array(
        'after_initialize', 'after_find', 'after_touch', 'before_validation', 'after_validation',
        'before_save', 'around_save', 'after_save', 'before_create', 'around_create',
        'after_create', 'before_update', 'around_update', 'after_update',
        'before_destroy', 'around_destroy', 'after_destroy', 'after_commit', 'after_rollback'       
    );

    protected static function callback_exists($callback)
    {
        #$class = get_called_class();
        $class = '\\Lycan\\Record\\Persistence';
        try {
            $reflection = new \ReflectionClass($class);
            if ( $method = $reflection->getMethod($callback))
                return $method;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function perform_callback_for($model, $persistence, $kind, $chain)
    {
        $obs = $norm = true;
        #$method_to_call = "{$chain}_{$method}";
        $methods_to_call = array("{$chain}_{$kind}");
        if ($kind == 'update') {
            $methods_to_call[] = "{$chain}_save";
        }
        foreach( $methods_to_call as $method ) {
            if (!in_array($method, self::$CALLBACKS)) continue;
            //Call callback method
            if ( method_exists($model, $method) )
                $norm = $model->$method();
            //Call observer callbacks if exist
            if ( $model instanceof \SplSubject )
                $obs = $model->notifySubject($method);
            if ( $obs !==false && $norm !== false ) 
                continue;
            else
                return false;
        }
        return true;
    }
}
