<?php 

namespace Lycan\Action;

use Lycan\Action\Router;

class Routes 
{

    public static $routers = array();
    
    public static $verbs = array( 'index', 'show', 'add', 'create', 'edit', 'update', 'destroy');
    
    public static function draw($block)
    {
        $map = new Router();
        $block($map);
    }

    public static function matchPath($path)
    {
        foreach( self::$routers as $router ) {
            if ( $router->match($path) ) return $router;
        }
        return false;
    }
}
