<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record;

require_once __DIR__ . DS . 'Exceptions.php';

abstract class Model extends \Lycan\Validations\Validate implements \SplSubject, Interfaces\Persistence
{
    public static $belongsTo = array();
    
    public static $hasMany = array();
    
    public static $hasOne = array();
    
    public static $hasAndBelongsToMany = array();
    
    public static $composed_of = array();

    private $_errors;

    protected $observers = array();

    private $_observers = array();

    /**
     * Persistence implementation attributes
     */

    /**
     * Data adapter
     *
     * @var \LycanRecord\Adapter
     * @access public
     */
    protected static $adapter;
    public static $columns = array();
    public static $table;
    public static $primary_key;
    protected $new_record=true;
    protected $destroyed=false;
    protected $readonly=false;
    public $attributes=array();

    /**
     * Callback methods
     */
    public $before_save = array(
        'belongs_to_associations_callbacks'
    );

    public $before_create = array(
        'belongs_to_associations_callbacks'
    );

    public $after_save = array(
        'has_one_associations_callbacks',
        'has_and_belongs_to_many_associations_callbacks'
    );

    public $after_create = array(
        'has_one_associations_callbacks',
        'has_and_belongs_to_many_associations_callbacks'
    );

    protected $association_cache = array();
    
    final function __construct($attributes=array(), $options=array()) 
    {
        $this->_create_persistence($attributes, $options);
        
        if ( !empty($this->observers) )
            foreach( $this->observers as $observer )
                $this->attach(new $observer());
    }

    private function _create_persistence($attributes=array(), $options=array())
    {
        $this->new_record = isset($options['new_record']) ? $options['new_record'] : true;
        $options['new_record'] = $this->new_record;
        $this->attributes = new Attributes(static::$columns, get_class($this), $options);

        if ($attributes)
            $this->attributes->assign($attributes, array('new_record'=>$this->new_record));       
    }

    public static function initWith($attributes, $options=array())
    {
        $options['new_record'] = false;
        return new static($attributes, $options);
    }

    /**
     * Association names can be called via magic methods as attributes.
     *
     * @params string $attribute
     *
     * @return mixed attribute or association instance
     * @throws \Lycan\Record\InvalidPropertyException
     *
     * @access public
     */
    public function __get($attribute)
    {
        if ( $assoc = $this->_association_for($attribute) )
            return $assoc;

        return $this->attributes->get($attribute);

        throw new InvalidPropertyException(get_class($this), $attribute);
    }

    public function __set($attribute, $value)
    {
        if (in_array($attribute, static::$columns))
            $this->attributes->set($attribute, $value);
        elseif ( $assoc = $this->_association_for($attribute) )
            return $assoc->set($value);
        else
            throw new InvalidPropertyException(get_class($this), $attribute);
    }

    public function __isset($attribute)
    {
         if ( $assoc = $this->_association_for($attribute) )
            return true;

        return null != $this->attributes->get($attribute);
    }

    public function __call($name, $args)
    {
        if ($assoc = $this->_association_for($name, isset($args[0]) ? $args[0] : null))
            return $assoc;
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

    final public function belongs_to_associations_callbacks()
    {
        foreach( $this->association_cache as $assoc ) {
            if ( ($assoc instanceof \Lycan\Record\Associations\BelongsTo) && $assoc->needSave() ) $assoc->saveAssociation();
        }
    }

    final public function has_one_associations_callbacks()
    {
        foreach( $this->association_cache as $assoc ) {
            if ( ($assoc instanceof \Lycan\Record\Associations\HasOne) && $assoc->needSave() ) $assoc->saveAssociation();
        }
    }

    final public function has_and_belongs_to_many_associations_callbacks()
    {
        foreach( $this->association_cache as $assoc ) {
            if (   $assoc instanceof \Lycan\Record\Associations\HasAndBelongsToMany
                && $assoc->needSave() ) $assoc->saveAssociation();
        }
    }
    /**
     * Finder methods
     */

    public static function findAllById(array $id, $options=array())
    {
        $options = self::_options_for_finder($options);
        return static::getAdapter()->getQuery(get_called_class(), $options)->where(array('id' => $id))->all();
    }

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
        if ( isset($options['readonly']) ) $this->readonly = $options['readonly'];

        return $options;
    }
    
    /**
     * Persistence implementation
     */
    public function isNewRecord()
    {
        return $this->new_record;
    }

    public function isPersisted()
    {
        return !($this->isNewRecord() || $this->isDestroyed()); 
    }

    public function isDestroyed()
    {
        return $this->destroyed;
    }


    public function isDirty()
    {
        return $this->attributes->isDirty();
    }

    public function reload()
    {
        $this->attributes->reload();
        if ($this->id() && $this->new_record) $this->new_record=false;
    }

    public function toggle($attribute) 
    {
    
    }

    public function touch($attribute)
    {

    }

    public function updateAttribute($name, $value)
    {
    
    }

    public function updateAttributes($attributes, $options=array())
    {
    
    }
    
    public function updateColumn($name, $value)
    {
    
    }

    public function decrement($attribute, $by=1)
    {
    
    }

    public function increment($attribute, $by=1) 
    {
    
    }

    public function delete()
    {
    
    }

    public function destroy()
    {
        $this->destroyed = true;
    }

    protected function id()
    {
        return $this->attributes[static::$primary_key];
    }

    protected function setId($id)
    {
        $this->attributes[static::$primary_key] = $id;
    }

    public function save($validate=true)
    {
        $v = $validate ? $this->run_validations() : true;
        return $v && $this->create_or_update();
    }

    protected function create_or_update()
    {
        $result = $this->new_record ? $this->_create() : $this->_update();
        return $result != false;
    }

    private function _update()
    {
        $static = get_class($this);
        $attributes = $this->attributes;
        $id = $this->id();

        return Callbacks::run_callbacks('update', $this, function() use ($static, $attributes, $id){
            
            $attribute_names = $attributes->keys();
            $attributes_with_values =  $attributes->attributesValues(false, false, $attribute_names);
            
            if ( empty($attributes_with_values) ) return 1;
            
            $query = $static::find()->where(array($static::$primary_key => $id))->compileUpdate($attributes_with_values);
            $res = $static::getAdapter()->query($query);
            $attributes->reload();

            return $res; 
        });

    }

    private function _create()
    {
        $static = get_class($this);
        $attributes = $this->attributes;
        $id = $this->id();
        $t = $this;

        return Callbacks::run_callbacks('create', $this, function() use ($static, $attributes, $id, $t){
            
            $attributes_with_values =  $attributes->attributesValues(!is_null($id));
            $query  = $static::getAdapter()->getQuery($static)->compileInsert($attributes_with_values);
            $new_id = $static::getAdapter()->insert($query);
            
            if ($static::$primary_key) {
                if (!isset($attributes[$static::$primary_key]))
                    $attributes[$static::$primary_key] = $new_id;
            }
            $t->reload(); 

            return $new_id;
        });
    }

    /**
     * Validation Implementation
     */
    
    /**
     * Overload this method to model, and write validation conditions.
     */
    protected function validations()
    {

    }

    protected function run_validations()
    {
        $method = new \ReflectionMethod($this,'validations');
        $method->setAccessible(true);
        $t = $this;

        return Callbacks::run_callbacks('validation', $this, function() use ($t, $method){
            $method->invoke($t);
            return $t->errors()->count() == 0;
        });
        return true;    
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

    public function __toString()
    {
        return get_class($this). "@" .spl_object_hash($this) ;
    }
}
