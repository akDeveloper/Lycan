<?php

namespace Lycan\Action;

class Session
{

    private $_cookie_name;
    
    public static $cookie_time = 10800; # 3 hours cookie

    public function __construct($cookie_name = "lycan_session")
    {
        $this->_cookie_name = $cookie_name;
        
        if ( !session_id() ){
          session_set_cookie_params(self::$cookie_time);

          if (isset($_COOKIE[$cookie_name]))
              session_id($_COOKIE[$cookie_name]);
          session_name($cookie_name);
          try{
            session_start();
          } catch (Exception $e){
          
          }
        }
    }

    public function __get($name)
    {
        return $this->getVar($name);
    }

    public function __set($name, $value)
    {
        $this->setVar($name, $value);
    }

    public function __isset($name)
    {
        return isset($_SESSION[$name]);
    }

    public function destroySession()
    {
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
    }

    public function flash($name, $string=NULL)
    {
        if ($string == NULL) {
            if (isset($_SESSION['flash'][$name])) {
                $flash = $_SESSION['flash'][$name];
                $this->unsetArrayVar("flash", $name);
                return $flash;
            }
            return NULL;
        } else {
            $this->setArrayVar("flash", $string, $name);
        }
    }

    public function getFlashes()
    {
        $return = isset( $_SESSION['flash'] ) ? $_SESSION['flash'] : array() ;
        if ( isset( $_SESSION['flash'] ) ) unset($_SESSION['flash']);
        return $return;
    }

    public function unsetVar($name)
    {
        unset($_SESSION[$name]);
    }

    public function unsetArrayVar($array, $name)
    {
        unset($_SESSION[$array][$name]);
    }

    public function setVar($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public function setArrayVar($name, $value, $index=null)
    {
        if ($index == null)
            $_SESSION[$name][] = $value;
        else
            $_SESSION[$name][$index] = $value;
    }

    public function getVar($name)
    {
        if (isset($_SESSION[$name]))
            return $_SESSION[$name];
        return null;
    }

}

?>
