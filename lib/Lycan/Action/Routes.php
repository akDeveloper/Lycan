<?php 

namespace Lycan\Action;

use Lycan\Action\Router;

class Routes 
{

    public static $routers = array();
    
    public static $verbs = array( 'index', 'add', 'create', 'edit', 'show', 'update', 'destroy');
    
    public static function draw($block)
    {
        $map = new Router();
        $block($map);
    }

    public static function urlFor($named_route, $params=array())
    {
        if (array_key_exists($named_route, self::$routers)) {
            return self::$routers[$named_route]->getUrlWith($params);
        }
        return null;
    }

    public static function matchPath($request)
    { 
        foreach( self::$routers as $router ) {
            if ( $router->match($request) ) return $router;
        }
        return false;
    }
}
