<?php 
namespace Lycan\Action;

use Lycan\Action\View;
use Lycan\Action\Response;
use Lycan\Action\Request;
use Lycan\Action\Routes;

class Controller 
{
    protected $layout;

    public static $layouts_path;
    public static $views_path;

    public $controller;
    public $action;
    public $params = array();

    protected $view;
    protected $action_view_path;

    protected $session;
    protected $request;

    public function __construct(Request $request, Router $router)
    {
        $this->session = $request->getSession();
        $this->controller = get_class($this);
        $this->action = $router->getAction();
        $this->params = array_merge($router->getParams(), $request->getParams());
        $this->action_view_path = self::$views_path . $this->controller . DS . $this->action . ".phtml"; 
        $this->layout_path = self::$layouts_path . $this->layout . ".phtml"; 
        $this->template = new View();
        $this->response = new Response();
    }

    public function dispatch()
    {
        if (method_exists($this, $this->action))
            $this->{$this->action}();
        else
            throw new \BadMethodCallException("Action {$this->controller}->{$this->action}() not exists.");

        # Render View
        if ($this->action == false) {
            $template_view = null;
        } else {
            $template_view = $this->action_view_path;
        }

        $local_assings = $this->_get_local_assigns();

        if (null !== $template_view)
            $this->template->yield = $this->template->render(array('file' => $template_view), $local_assings);

        if ($this->layout) { /* Load layout if exists */
            $this->response->template = $this->template->render(array('file' => $this->layout_path, $local_assings));
        } else { /* Load action view if exists */
            $this->response->template = $this->template->yield;
        }
        
        #$this->response->status = $this->response_status;
        echo $this->response->to_string();

    }

    public static function bootstrap($url=null)
    {
        try {
            self::$views_path = APP_PATH . 'app' . DS . 'views/';
            self::$layouts_path = self::$views_path . 'layouts/';
            
            $request = new Request($url);
            $router = Routes::matchPath($request->getPath());
            
            if (false === $router)
                throw new \Exception('Invalid request');

            $controller = $router->getController();
            $action     = $router->getAction();
            $controller = new $controller($request, $router);
            $controller->dispatch();
             
        } catch (\Exception $e ) {
            $ae = new \Lycan\Exception($e->getMessage(), $e->getCode(), $e);
            echo $ae;
        }
    }

    private function _get_local_assigns()
    {
        $attr = array();

        $ref = new \ReflectionObject($this);
        $props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $pro) {
            false && $pro = new \ReflectionProperty();
            $attr[$pro->getName()] = $pro->getValue($this);
        }
        return $attr;
    }
    
}
