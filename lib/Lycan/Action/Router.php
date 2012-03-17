<?php 

namespace Lycan\Action;

use Lycan\Support\Inflect;

class Router
{
    protected $namespace;

    protected $url;

    protected $controller;

    protected $action;
    
    protected $method='get';

    protected $name;

    protected $params;

    protected $format;

    private $_default_values;

    public function __construct($name=null, $url=null, $controller=null, $action=null,  $namespace=null, $method='get', $format=null)
    {
        $this->url          = isset($namespace) ? $namespace . "/" . $url : $url;
        $this->controller   = $controller;
        $this->action       = $action;
        $this->method       = $method;
        $this->namespace    = $namespace;
        $this->name         = $name;
        $this->format       = $format;
    }

    public function getNamespace()
    {
        return Inflect::camelize($this->namespace);
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getController()
    {
        return $this->namespace 
            ? Inflect::camelize($this->namespace) . "\\" . Inflect::camelize($this->controller) . "Controller"
            : Inflect::camelize($this->controller) . "Controller";
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getParams()
    {
        $params = $this->params;
        unset($params[':controller']);
        unset($params[':action']);
        return $params ?: array();
    }

    public function getUrlWith($params=array())
    {
        if (empty($params))
            return "/" . $this->url;
        return "/" . str_replace(array_keys($params), $params, $this->url); 
    }

    public function name_space($name, $block)
    {
        $router = new Router(null, null, null, null, $name);
        $block($router);
    }

    public function resources($name, $options=array())
    {
        $only = isset($options['only']) ? $options['only'] : array();
        $only = empty($only) ? \Lycan\Action\Routes::$verbs : $only;
        foreach ($only as $verb)
        {
            $router = self::_verb_to_router($verb,$name,$this->namespace);
            Routes::$routers[$router->getName()] = $router;
        }

    }

    public function connect($url, $options=array(), $name=null)
    {
        if ( trim($url) == "" && empty($options) )
            throw new \InvalidArgumentException("url and or options must be set");

        $this->url = ( $this->namespace ? $this->namespace . "/" : null ) . $url;

        $this->url = substr($this->url, -1, 1) == "/" 
            ? substr($this->url, 0, -1) 
            : $this->url;

        $this->controller = isset($options['controller']) ? $options['controller'] : null;
        $this->action     = isset($options['action']) ? $options['action'] : null;
        $this->method     = isset($options['method']) ? $options['method'] : 'get';
        $this->format     = isset($options['format']) ? $options['format'] : null;
        $this->name       = isset($options['name'])   ? $options['name']   : null;

        if (  empty($options) || $this->controller == null) {
            $this->_default_values = true;
        }
        null === $name ? Routes::$routers[] = clone $this : Routes::$routers[$name] = clone $this;
        
        $this->_default_values = null;
    }

    private function _url_combine($path)
    {
        if ( !empty($this->params) ) return $this->params;

        $format = false;
        
        $match_url = explode('/', $path);
        if ( $pos = strrpos($path, '.') ) {
            $ext = substr($path, $pos);
            $match_url[] = substr($ext, 1);
            $format = true;
        }
        $pos = strrpos($this->url, '.');
        if ( false == $format && $pos ) return array();
        
        $route_url = explode('/', $this->url);
        if ( $format && $pos ) {
            $ext = substr($this->url, $pos);
            $route_url[] = substr($ext,1);
        } elseif($format == true) {
            array_pop($match_url);
            $format = false;
        }

        if (count($route_url) >= 1 && count($match_url) >= 1 
            && count($route_url) == count($match_url) ) 
        {
            $return = array();
            $combine = array_combine($route_url, $match_url);
            foreach ($combine as $key => $value) {
                if ($key == $value)
                    continue;
                if ( $format ) {
                    $e = explode('.',$key);
                    $v = explode('.',$value);
                    $return[$e[0]] = $v[0];
                    $this->format = $v[0];
                } else {
                    $return[$key] = $value;
                }
            }
            $this->params = $return;
            return $return;
        }
        return array();
    }

    public function match($request)
    {
        if ($this->method != $request->getMethod()) return false;
        $path = $request->getPath();
        $regx_url = preg_replace("/:[a-z]+/","([^/]+)", $this->url);
        $regx_url =  str_replace('/','\/', $regx_url);
        $regx_url =  str_replace('.','\.', $regx_url);
        if (preg_match("/^" . $regx_url . "$/", $path)) {
            $a = $this->_url_combine($path);
            if ( $this->_default_values ) {
                if ( empty($a) ) return false;
                $this->controller = $a[':controller'];
                $this->action = $this->action == null ? $a[':action'] : $this->action;
                return true;
            }
            return true;
        }
        return false;
    }

    private static function _verb_to_router($verb, $name, $namespace=null)
    {
        $singular = Inflect::singularize($name);
        switch ($verb) {
            case 'index':
                $controller = $name;
                $action     = 'index';
                $url        = $name;
                $name       = ($namespace ? "{$namespace}_" : null) . $name;
                return new Router($name, $url, $controller, $action, $namespace);
                break;
            case 'add':
                $controller = $name;
                $action     = 'add';
                $url        = $name . '/new';
                $name       = ($namespace ? "{$namespace}_" : null) . "add_" . $singular;
                return new Router($name, $url, $controller, $action, $namespace);
                break;
            case 'create': 
                $controller = $name;
                $action     = 'create';
                $url        = $name;
                $name       = ($namespace ? "{$namespace}_" : null) . "create_" . $singular;
                return new Router($name, $url, $controller, $action, $namespace, 'post');
                break;
            case 'edit': 
                $controller = $name;
                $action     = 'edit';
                $url        = $name . '/:id/edit';
                $name       = ($namespace ? "{$namespace}_" : null) . "edit_" . $singular;
                return new Router($name, $url, $controller, $action, $namespace);
                break;
            case 'show':
                $controller = $name;
                $action     = 'show';
                $url        = $name . "/:id";
                $name       = ($namespace ? "{$namespace}_" : null) . "show_" . $singular;
                return new Router($name, $url, $controller, $action, $namespace);
                break;
            case 'update': 
                $controller = $name;
                $action     = 'update';
                $url        = $name . '/:id';
                $name       = ($namespace ? "{$namespace}_" : null) . "update_" . $singular;
                return new Router($name, $url, $controller, $action, $namespace, 'put');
                break;   
            case 'destroy': 
                $controller = $name;
                $action     = 'destroy';
                $url        = $name . '/:id';
                $name       = ($namespace ? "{$namespace}_" : null) . 'destroy_' . $singular;
                return new Router($name, $url, $controller, $action, $namespace, 'delete');
                break;
        }
    }

    public function __toString()
    {
        return $this->url;
    }
}
