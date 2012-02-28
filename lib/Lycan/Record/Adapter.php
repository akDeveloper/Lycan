<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

abstract class Adapter 
{
    /**
     * The database connection resource
     *
     * @var Resource
     * @access protected
     */
    protected $connection;

    /**
     * Database host name
     *
     * @var string
     * @access protected
     */
    protected $host;

    /**
     * Database user name
     *
     * @var string
     * @access protected
     */
    protected $user;

    /**
     * Database password
     *
     * @var string
     * @access protected
     */
    protected $password;

    /**
     * Database name
     *
     * @var string
     * @access protected
     */
    protected $database;

    /**
     * Database charset
     *
     * @var string
     * @access protected
     */
    protected $charset;

    abstract protected function connection();

    /**
     * Escapes a string to performa a safe sql query
     *
     * @param string $string the string to escape
     * @access public
     * @abstract
     *
     * @return string the escaped string
     */
    abstract public function escapeString($string);

}
