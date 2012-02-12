<?php
namespace Lycan;
class Exception extends \Exception
{
    protected $previous_exception;
    protected $e_message;
    protected $e_code;
    protected $e_file;
    protected $e_line;
    protected $e_class;

    public function __construct($message, $code=0, \Exception $previous=null)
    {
        parent::__construct($message,$code);
        $this->previous_exception = $previous;
        $exception = $previous ?: $this;
        $this->e_message = $exception->getMessage();
        $this->e_code = $exception->getCode();
        $this->e_line = $exception->getLine();
        $this->e_file = $exception->getFile();
        $this->e_class = get_class($exception);
        $this->e_trace = $exception->getTrace();
    }

    public function __toString()
    {
        return $this->dispatch();
    }

    public function dispatch()
    {
        if (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
            return $this;
        } else {
            ob_end_clean();
            $file = \Lycan\Action\Controller::$layouts_path . "error_layout.phtml";
            ob_start();
            ob_implicit_flush(0);
            include $file;
            $body = ob_get_contents();
            ob_end_clean();

            return $body;
        }
    }
}
