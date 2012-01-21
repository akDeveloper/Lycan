<?php

namespace Lycan\Action;

class Response
{

    public $status;
    public $template;
    public $headers = array();

    public function __construct($status = "200 OK")
    {
        $this->status = $status;
    }

    public function __toString()
    {
        return $this->to_string();
    }

    public function to_string()
    {
        if ( !$this->_is_cli() ){
            header("HTTP/1.0 " . $this->status);
            foreach ($this->headers as $key => $value) {
                header($key . ": " . $value);
            }
        }
        if ( null === $this->template  ){
            switch ($this->status) {
                case "404 Not Found":
                    break;
            }
        }
        return $this->template;
    }
    private function _is_cli() {

        if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
            return true;
        } else {
            return false;
        }
    }
}

?>
