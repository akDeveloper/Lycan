<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Lycan\Record\Associations;

abstract class Single extends \Lycan\Record\Associations implements Interfaces\Single
{
    /**
     * Tries to execute missing methods 
     * from @see Lycan\Record\Association::result_set instance
     *
     * @return mixed the result of missing method if will find one.
     */
    public function __call($method, $args)
    {
        return $this->magic_method_call($method, $args, $this->fetch());
    }

    /**
     * Tries to return the value of $attribute variable
     * from @see Lycan\Record\Association::result_set instance
     * where in this case is a @see Lycan\Record\Model instance
     *
     * @return mixed the value of $attribute 
     */
    public function __get($attribute)
    {   
        $fetch = $this->fetch();
        return  $fetch ? $fetch->$attribute : null;
    }


    /**
     *
     * @return Lycan\Record\Model the association instance
     */
    protected function fetch()
    {
        if (   null == $this->result_set 
            || $this->result_set instanceof \Lycan\Record\Query
        ){
            $this->result_set = $this->find() ? $this->find()->fetch() : null;
        }

        return $this->result_set;
    }

    public function setWith(\Lycan\Record\Model $associate)
    {
        $this->result_set = $associate;
    }

    public function isNull()
    {
        return null === $this->fetch() || $this->fetch() instanceof \Lycan\Record\Null;
    }

    public function build($attributes=array())
    {
        $class = $this->association;
        $new = new $class($attributes);
        $this->set($new);
        return $new;
    }

    public function create($attributes=array())
    {
        $class = $this->association;
        $new = new $class($attributes);
        $new->save();
        $this->set($new);
        return $new;
    }

}
