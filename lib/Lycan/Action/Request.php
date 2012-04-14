<?php

namespace Lycan\Action;

class Request
{

    private $_host;
    private $_subdomain;
    private $_port;
    private $_url;
    private $_path;
    private $_query;
    private $_query_array = array();
    private $_method;
    private $_hidden_method;
    private $_referer;
    private $_protocol;
    private $_response_status = "200 OK";
    private $_document_format;
    private $_routes;
    private $_session;

    public $params = array();

    public function getSession()
    {
        return $this->_session;
    }

    public function getResponseStatus()
    {
        return $this->_response_status;
    }

    public function getHost()
    {
        return $this->_host;
    }

    public function getSubdomain()
    {
        return $this->_subdomain;
    }

    public function getPort()
    {
        return $this->_port;
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function getReferer()
    {
        return $this->_referer;
    }

    public function getFormat()
    {
        return $this->_document_format;
    }

    public function isXHttpRequest()
    {
        return $this->_http_x_request;
    }

    public function getParams()
    {
        return $this->_query_array;
    }

    public function __construct($url = null)
    {

        $this->_url = null === $url ? (isset($_SERVER['REQUEST_URI']) ? urldecode($_SERVER['REQUEST_URI']) : null ) : urldecode($url);

        $this->_parse_request_url();

        $this->_parse_post_data();

        $this->params = $this->_query_array = $this->sanitize_array($this->_query_array);
        \Lycan\Support\Logger::getLogger()->log(print_r($this->params, true));
    }

    private function _parse_request_url()
    {

        $parse_url = parse_url($this->_url);

        $this->_host = isset($parse_url['host']) ? $parse_url['host'] : $_SERVER["HTTP_HOST"];

        preg_match('/(^[a-zA-Z0-9-]+)\.[a-zA-Z0-9-]+\.[a-zA-Z0-9-]+$/', $this->_host, $subdomain);

        $this->_subdomain = isset($subdomain[1]) ? $subdomain[1] : null;

        $this->_port = isset($parse_url['port'])
            ? $this->_port = $parse_url['port']
            : (isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80);

        $this->_method = strtolower(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');

        $this->_referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;

        $this->_protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : "HTTP/1.0";

        $this->_http_x_request = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' );

        $path = $parse_url['path'];

        # strip first and last backslash (/) if exist
        $this->_path = substr($path, -1, 1) == "/" 
            ? substr($path, 0, -1) 
            : $path;
        $this->_path = substr($this->_path, 0, 1) == "/" 
            ? substr($this->_path, 1) 
            : $this->_path;

        if (isset($parse_url['query'])) {
            $this->_query = $parse_url['query'];
            parse_str($parse_url['query'], $this->_query_array);
        }

    }

    private function _parse_post_data()
    {
        if (isset($_POST) && !empty($_POST)) {
            $this->_query_array = array_merge($this->_query_array, $_POST);
            $this->_method = isset($_POST['_method']) && in_array($_POST['_method'], array('put','delete')) 
                ?  $_POST['_method']
                : 'post';

        }
    }

    public function sanitize_array(array $array)
    {
        foreach ($array as $k => $v) {
            if (is_array($v))
                $array[$k] = $this->sanitize_array($v);
            else
                $array[$k] = htmlspecialchars( get_magic_quotes_gpc() ? stripslashes($v) : $v, ENT_QUOTES, 'UTF-8');
        }
        return $array;
    }

}

?>
