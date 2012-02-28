<?php

namespace Lycan\Action;

class View
{

    public $yield;

    public function render($options = array(), $local_assigns = array())
    {

        foreach ($local_assigns as $k => $v) {
            $this->$k = $v;
        }

        if (isset($options['text'])) {
            $content = $options['text'];
        } elseif (isset($options['file'])) {
            $file = $options['file'];
        }

        ob_start();
        ob_implicit_flush(0);
        try {
            include $file;
            $body = ob_get_contents();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        ob_end_clean();

        return $body;
    }

    public function yield()
    {
        $numargs = func_num_args();
        if (0 === $numargs) {
            return $this->yield;
        } elseif (1 === $numargs) {
            $arg = func_get_arg(0);
            return isset($this->$arg) ? $this->$arg : null;
        }
    }
}

?>
