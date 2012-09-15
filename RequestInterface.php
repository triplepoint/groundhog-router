<?php
namespace Groundhog\Router;

interface RequestInterface
{
    /**
     * Returns the path being requested relative to the executed script.
     *
     * The path info always starts with a /.
     *
     * Suppose this request is instantiated from /mysite on localhost:
     *
     *  * http://localhost/mysite              returns an empty string
     *  * http://localhost/mysite/about        returns '/about'
     *  * htpp://localhost/mysite/enco%20ded   returns '/enco%20ded'
     *  * http://localhost/mysite/about?var=1  returns '/about'
     *
     * @return string The raw path (i.e. not urldecoded)
     */
    public function getPathInfo();

    /**
     * Generates a normalized URI for the Request.
     *
     * @return string A normalized URI for the Request
     */
    public function getUri();

    /**
     * Returns the host name.
     *
     * @return string
     */
    public function getHost();

    /**
     * Gets the request method.
     *
     * The method is always an uppercased string.
     *
     * @return string The request method
     */
    public function getMethod();
}