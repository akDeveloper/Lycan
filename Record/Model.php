<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

abstract class Model extends Callbacks implements \SplSubject
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

    protected $persistence;

    protected $logger;

    final function __construct($attributes=array(), $options=array()) 
    {
        #parent::__construct($attributes, $options);
        $this->persistence = new Persistence(get_called_class(),$attributes, $options);
        
        if ( !empty($this->observers) )
            foreach( $this->observers as $observer )
                $this->attach(new $observer());
        
        $this->logger = new \Lycan\Record\Logger();
        
    }

    public static function initWith($attributes, $options=array())
    {
        $options['new_record'] = false;
        return new static($attributes, $options);
    }

    public function __get($attribute)
    {
        return $this->persistence->attributes->get($attribute);
    }

    public function __set($attribute, $value)
    {
        $this->persistence->attributes->set($attribute, $value);
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
    }

    public function notifySubject($method)
    {
        foreach ( $this->_observers as $observer ) {
            $observer->updateSubject($this, $method);
        }
    }

    public static function setAdapter($type, $options)
    {
        if ( null === $type )
            throw new \InvalidArgumentException('You should define an adapter type and $options for setup.');

        $adapter = "\\Lycan\\Record\\Adapter\\$type";
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
