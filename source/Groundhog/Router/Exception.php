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
     * @param number     $code
     * @param array      $context
     * @param \Exception $previous
     *
     * @return void
     */
    public function __construct($message = '', $code = 0, $context = array(), \Exception $previous = null)
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
