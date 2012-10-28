<?php

namespace Groundhog\Router;

/**
 * The general exception thrown by the router.
 */
class Exception extends \Exception
{
    /**
     * The Exception's stored context structure
     *
     * @var array
     */
    protected $context;

    /**
     * Adds a context array to the standard exception
     *
     * @param string     $message
     * @param array      $context
     * @param number     $code
     * @param \Exception $previous
     *
     * @return void
     */
    public function __construct($message = '', $context = array(), $code = 0, \Exception $previous = null)
    {

    }

    /**
     * Get the stored context array
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
