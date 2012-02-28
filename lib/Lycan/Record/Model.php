<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

require_once __DIR__ . DS . 'Exceptions.php';

abstract class Model extends Callbacks implements \SplSubject
{
    /**
     * Database adapter
     *
     * @var \LycanRecord\Adapter
     * @access public
     */
    protected static $adapter;

    public static $columns = array();

    public static $table;

    public static $primary_key;
 
    public static $belongsTo = array();
    
    public static $hasMany = array();
    
    public static $hasOne = array();
    
    public static $hasAndBelongsToMany = array();
    
    public static $composed_of = array();

    protected $observers = array();

    private $_observers = array();

    protected $persistence;

    protected $before_save = array(
        'belongs_to_associations_callbacks'
    );

    protected $before_create = array(
        'belongs_to_associations_callbacks'
    );

    protected $after_save = array(
        'has_one_associations_callbacks'
    );

    protected $after_create = array(
        'has_one_associations_callbacks'
    );

    protected $association_cache = array();
    
    final function __construct($attributes=array(), $options=array()) 
    {
        $this->persistence = new Persistence(get_called_class(),$attributes, $options);
        
        if ( !empty($this->observers) )
            foreach( $this->observers as $observer )
                $this->attach(new $observer());

        $this->reflection_properties();
    }

    public static function initWith($attributes, $options=array())
    {
        $options['new_record'] = false;
        return new static($attributes, $options);
    }

    /**
     * Persistence attributes can be accessed from Model class. Also 
     * association names can be called via magic methods.
     *
     * @params string $attribute
     *
     * @return mixed persistence attribute or association instance
     * @throws \Lycan\Record\InvalidPropertyException
     *
     * @access public
     */
    public function __get($attribute)
    {
        if ( $assoc = $this->_association_for($attribute) )
            return $assoc;

        return $this->persistence->attributes->get($attribute);

        throw new InvalidPropertyException(get_class($this), $attribute);
    }

    public function __set($attribute, $value)
    {
        if (in_array($attribute, static::$columns))
            $this->persistence->attributes->set($attribute, $value);
        elseif ( $assoc = $this->_association_for($attribute) )
            return $assoc->set($value);
        else
            throw new InvalidPropertyException(get_class($this), $attribute);
    }

    public function __call($name, $args)
    {
        if ($assoc = $this->_association_for($name, isset($args[0]) ? $args[0] : null))
            return $assoc;
    }

    public function isNewRecord()
    {
        return $this->persistence->isNewRecord();
    }

    /**
     * Associations
     */
    private function _association_for($name, $reload=false)
    {
        $association = $this->get_association_instance($name);

        if ( null == $association || $reload == true ) {
            if ($association = Associations::buildAssociation($name, $this))
                $this->set_association_instance($name, $association);
            if (null == $association ) return false;
        }
        return $association;
    }

    protected function set_association_instance($name, $instance)
    {
        $this->association_cache[$name] = $instance;
    }
        
    protected function get_association_instance($name)
    {
        return isset($this->association_cache[$name]) ? $this->association_cache[$name] : null;
    }

    final protected function belongs_to_associations_callbacks()
    {
        foreach( $this->association_cache as $assoc ) {
            if ( ($assoc instanceof \Lycan\Record\Associations\BelongsTo) && $assoc->needSave() ) $assoc->saveAssociation();
        }
    }

    final protected function has_one_associations_callbacks()
    {
        foreach( $this->association_cache as $assoc ) {
            if ( ($assoc instanceof \Lycan\Record\Associations\HasOne) && $assoc->needSave() ) $assoc->saveAssociation();
        }
    }

    /**
     * Finder methods
     */

    public static function find($id=null, $options=array())
    {
        $options = self::_options_for_finder($options);
        if ( null==$id )
            return static::getAdapter()->getQuery(get_called_class(), $options);
        else
            return static::getAdapter()->getQuery(get_called_class(), $options)->where(array('id' => (int) $id))->fetch();
    }

    public static function all($options=array())
    {
        $options = self::_options_for_finder($options);
        return static::getAdapter()->getQuery(get_called_class(), $options)->all();
    }

    public static function first($options=array())
    {
        $options = array_merge($options, array('fetch_method' => 'one'));
        return self::find(null, $options)->first();
    }

    public static function last($options=array())
    {
        $options = array_merge($options, array('fetch_method' => 'one'));
        return self::find(null, $options)->last();
    }

    private static function _options_for_finder($options)
    {
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

    public static function establishConnection($type, $options)
    {
        if (null === $type || empty($options))
            throw new \InvalidArgumentException('You should define an adapter type and $options for setup.');

        $adapter = "\\Lycan\\Record\\Adapter\\$type";
        $class = get_called_class();
        static::$adapter[$class] = new $adapter($options);
    }

    public static function getAdapter()
    {
        $class = get_called_class();
        return isset(static::$adapter[$class])
            ? static::$adapter[$class]
            : static::$adapter["Lycan\\Record\\Model"];
    }

}
