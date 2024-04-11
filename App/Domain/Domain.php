<?php

namespace App\Domain;

#objects
use Ramsey\Uuid\Uuid;

#exceptions
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use App\Exception\InvalidObjectException;
use App\Exception\InvalidAttributeException;

#functions
use function GuzzleHttp\json_encode;

/**
 * 
 */
class Domain
{

    protected $structure = [];
    protected $content = [];

    /**
     * 
     */
    public function __construct(array $constructor)
    {

        if(count(array_intersect_key($this->structure, $constructor)) === count($this->structure)) 
        {
            $this->content = $constructor;
            $id = Uuid::uuid4();
            $this->content['id'] = $id->toString();
            return $this;
        }
        
        throw new InvalidObjectException("unable to instantiate object");
        
    }

    /**
     * 
     */
    public function __get(string $var)
    {
        if(array_key_exists($var, $this->content)) 
        {
            return $this->content[$var];
        }

        throw new InvalidAttributeException();

    }

    /**
     * 
     */
    public function __set(string $var, $value) 
    {
        if(array_key_exists($var, $this->content)) 
        {
            $this->content[$var] = $value;
            return $this;
        }

        throw new InvalidAttributeException();
    }


    /**
     * 
     */
    public function __clone()
    {
        return new self($this->content);
        
    }

    public function __toString()
    {
        return json_encode($this->content);
    }

    
    /**
     * Get Data stored for request
     */
    public function getData() {
        return $this->content;
    }

}

