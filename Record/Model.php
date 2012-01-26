<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

abstract class Model extends Persistence  implements \SplSubject
{
    /**
     * Database adapter
     *
     * @var \LycanRecord\Adapter
     * @access public
     */
    protected static $adapter;

    public static $belongsTo = array();
    
    public static $hasMany = array();
    
    public static $hasOne = array();
    
    public static $hasAndBelongsToMany = array();

    protected $observers = array();

    private $_observers = array();

    final function __construct($attributes=array(), $options=array()) 
    {
        parent::__construct($attributes, $options);

        if ( !empty($this->observers) )
            foreach( $this->observers as $observer )
                $this->attach(new $observer());
        
    }

    public function __get($attribute)
    {
       return $this->attributes[$attribute];
    }

    public function __set($attribute, $value)
    {
        $this->attributes->set($attribute, $value);
    }
    /**
     * Finder methods
     */

    public static function find($as_object=false, $options=array())
    {
        $options = self::_options_for_finder($as_object, $options);
        $options = array_merge($options, array('fetch_method' => 'one'));
        return static::$adapter->getQuery(get_called_class(), $options);
    }

    public static function all($as_object=false, $options=array())
    {
        $options = self::_options_for_finder($as_object, $options);
        $options = array_merge($options, array('fetch_method' => 'all'));
        return static::$adapter->getQuery(get_called_class(), $options)->all();
    }

    public static function first($as_object=false, $options=array())
    {
        $options = array_merge($options, array('fetch_method' => 'one'));
        return self::find($as_object, $options)->first();
    }

    public static function last($as_object=false, $options=array())
    {
        $options = array_merge($options, array('fetch_method' => 'one'));
        return self::find($as_object, $options)->last();
    }

    private static function _options_for_finder($as_object, $options)
    {
        $options['as_object'] = $as_object;
        if ( isset($options['readonly']) ) $this->readony = $options['readonly'];

        return $options;
    }


    /**
     * SplSubject implementation
     */
    public function attach(\SplObserver $observer)
    {
        $this->_observers[get_class($observer)] = $observer;
    }

    public function detach(\SplObserver $observer)
    {
        unset( $this->_observers[get_class($observer)] );
    }

    public function notify()
    {
        foreach ( $this->_observers as $observer ) {
            $observer->update($this);
        }
    }

    

    /**
     * @params array $options options hash for database connection and database 
     *                        adapter. Accepted variables for hash are:
     *                        host, port, user, password, database, charset, 
     *                        adapter.
     *                         
     */
    public static function setAdapter($options)
    {
        $adapter = isset($options['adapter']) ? $options['adapter'] : null;
        if ( null === $adapter )
            throw new \InvalidArgumentException('You should define an adapter to $options hash.');

        $adapter = "\\Lycan\\Record\\Adapter\\$adapter";
        static::$adapter = new $adapter($options);

    }

    public static function establishConnection($options)
    {
        static::setAdapter($options); 
    }

    public static function getAdapter()
    {
        return static::$adapter;
    }

}
