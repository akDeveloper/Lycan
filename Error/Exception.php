<?php
namespace Lycan\Error;

class Exception extends \ErrorException
{

    public function __toString()
    {
        return $this->dispatch();
    }

    public function dispatch()
    {
        $exception = $this->getPrevious() ?: $this;
        $this->e_message = $exception->getMessage();
        $this->e_code = $exception->getCode();
        $this->e_line = $exception->getLine();
        $this->e_file = $exception->getFile();
        $this->e_class = get_class($exception);
        $this->e_trace = $exception->getTrace();
        
        $ob_get_status = ob_get_status();
        if (!empty($ob_get_status)) ob_end_clean();

        $file = \Lycan\Action\Controller::$layouts_path . "error_layout.phtml";
        ob_start();
        ob_implicit_flush(0);
        include $file;
        $body = ob_get_contents();
        ob_end_clean();

        return $body;
    }
}
