<?php

namespace Groundhog\Router;

/**
 * Objects that implement this interface are responsible for building exceptions.
 *
 * @author jhanson
 *
 */
interface ExceptorInterface
{
    /**
     * Build and return a generic exception
     *
     * @param string  $message
     * @param integer $code
     * @param \Exception $previous
     *
     * @return \Exception the generated exception
     */
    public function exception($message = '', $code = 0, \Exception $previous = null);

    /**
     * Build and return an exception suitable for representing an HTTP status
     *
     * @param string     $private_message    An error message, not presented to the end user and suitable for internal error reporting
     * @param integer    $http_status_code   The HTTP status code to which this exception maps
     * @param array      $additional_headers Any additional HTTP headers to include in this exception, in the form array('header-name' => 'header-value')
     * @param string     $public_message     An error message suitable for showing to the end user
     * @param \Exception $previous           An optional Exception that led to this Exception
     *
     * @return \Exception the generated exception
     */
    public function httpException($private_message = '', $http_status_code = 0, array $additional_headers = array(), $public_message = null, \Exception $previous = null);
}
