<?php

namespace Groundhog\Router;

/**
 * This exception represents an error where a route
 * exists, but the requested method is not allowed.
 * It will usually provide a set of allowed methods.
 */
class ExceptionMethodNotAllowed extends Exception
{
    /**
     * The methods that would be allowed
     *
     * @var array
     */
    protected $allowed_methods;

    /**
     * Adds a context array to the standard exception
     *
     * @param string[]   $allowed_methods The set of methods that would be allowed
     * @param string     $message
     * @param integer    $code
     * @param \Exception $previous
     *
     * @return void
     */
    public function __construct(array $allowed_methods = array(), $message = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->allowed_methods = $allowed_methods;
    }

    /**
     * Get the methods that would be allowed
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->allowed_methods;
    }
}
