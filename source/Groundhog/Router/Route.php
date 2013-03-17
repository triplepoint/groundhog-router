<?php
namespace Groundhog\Router;

class Route
{
    /**
     * Absolute-with-protocol routes are of the form "http://www.whatever.com/some/route"
     * @var integer
     */
    const ROUTE_TYPE_ABSOLUTE_PROTOCOL = 1;

    /**
     * Absolute-with-protocol routes with wildcards are of the form "http://www.whatever.com/some/route/#1"
     * @var integer
     */
    const ROUTE_TYPE_ABSOLUTE_PROTOCOL_WITH_WILDCARDS = 2;

    /**
     * Absolute-with-domain routes are of the form "//www.whatever.com/some/route"
     * @var integer
     */
    const ROUTE_TYPE_ABSOLUTE_DOMAIN = 3;

    /**
     * Absolute-with-domain routes with wildcards are of the form "//www.whatever.com/some/route/#1"
     * @var integer
     */
    const ROUTE_TYPE_ABSOLUTE_DOMAIN_WITH_WILDCARDS = 4;

    /**
     * Absolute-with-path routes are of the form "/some/route"
     * @var integer
     */
    const ROUTE_TYPE_ABSOLUTE_PATH = 5;

    /**
     * Absolute-with-path routes with wildcards are of the form "/some/route/#1"
     * @var integer
     */
    const ROUTE_TYPE_ABSOLUTE_PATH_WITH_WILDCARDS = 6;

    /**
     * One of the class-constant route types.
     *
     * @var integer
     */
    protected $route_type;

    /**
     * The HTTP method of the route this object represents
     *
     * @var string
     */
    protected $route_http_method;

    /**
     * The regular expression which will match request strings for this route
     *
     * @var string
     */
    protected $route_regex;

    /**
     * The class name of the route handler to which this route will be directed
     *
     * @var string
     */
    protected $class_name;

    /**
     * The parameter order of the wildcard matches in the route, mapping their order in the HTTP
     * request to their order in the method's interface
     *
     * @var array
     */
    protected $parameter_order;

    /**
     * The Route string, as it appeared in the definition
     *
     * @var string
     */
    protected $raw_route_string;

    /**
     * Construct a new Route and define all the information it contains
     *
     * @param integer $route_type
     * @param string $route_http_method
     * @param string $route_regex
     * @param string $class_name
     * @param array $parameter_order
     * @param string $raw_route_string
     */
    public function __construct($route_type, $route_http_method, $route_regex, $class_name, $parameter_order, $raw_route_string)
    {
        $this->route_type        = $route_type;
        $this->route_http_method = $route_http_method;
        $this->route_regex       = $route_regex;
        $this->class_name        = $class_name;
        $this->parameter_order   = $parameter_order;
        $this->raw_route_string  = $raw_route_string;
    }

    /**
     * @return integer
     */
    public function getRouteType()
    {
        return $this->route_type;
    }

    /**
     *
     * @return string
     */
    public function getRouteHttpMethod()
    {
        return $this->route_http_method;
    }

    /**
     *
     * @return string
     */
    public function getRouteRegex()
    {
        return $this->route_regex;
    }

    /**
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->class_name;
    }

    /**
     *
     * @return array
     */
    public function getParameterOrder()
    {
        return $this->parameter_order;
    }

    /**
     *
     * @return string
     */
    public function getRawRouteString()
    {
        return $this->raw_route_string;
    }

    /**
     * Extract any wildcard values from an incoming request.
     *
     * Routes can have wildcards in them in an order different from the handler method's parameter order.
     * For example, /route/#3/$1/$2 has an integer wildcard at position 3, and string wildcards at positions 1
     * and 2.  This would imply that the method should be called such that:
     * handler_method( {parameter $1}, {parameter $2}, {parameter #3} );
     *
     * This function takes an incoming page request (which contains the actual parameter values), and takes
     * the regular expression known to match the request (derived from the Route annotation), and a known order
     * of the parameters in the Route (also derived from the Route annotation), and returns an array of the
     * actual parameter values, in the appropriate order.
     *
     * The idea is to be able to pass the returned parameters to the handler method.
     *
     * @param RequestInterface $request the incoming page request, appropriate for being matched by the regex
     *
     * @return array
     */
    public function extractParametersFromRequest(RequestInterface $request)
    {
        $request_types = self::getPathsByRouteType($request);
        $request_string = $request_types[$this->route_type];

        // Return the extracted results
        preg_match_all($this->route_regex, $request_string, $matches, PREG_SET_ORDER);
        $result = $matches[0];
        array_shift($result);

        // If the result has content
        if (!empty($result)) {
            // Create a new array using the route parameter order as the keys and the request values as the values
            $result = array_combine(unserialize($this->parameter_order), array_values($result));

            // Sort the array by the indices (to get them in the right order)
            ksort($result, SORT_NUMERIC);
        }

        // Return the array of sorted parameters
        return $result;
    }

    /**
     * Given a request object, return an array of the various request
     * fragments that would correspond to the various route types.  This is
     * useful for evaluating the request against the routes.
     *
     * @param RequestInterface $request
     *
     * @return array
     */
    public static function getPathsByRouteType(RequestInterface $request)
    {
        $entire_uri_without_queries = (strpos($request->getUri(), '?') !== false) ?
            substr($request->getUri(), 0, strpos($request->getUri(), '?')) :
            $request->getUri();

        $return = array(
            Route::ROUTE_TYPE_ABSOLUTE_PROTOCOL =>                $entire_uri_without_queries,	// Absolute URL's with protocol, are an entire HTTP request URL.  Compare against the entire request URL.
            Route::ROUTE_TYPE_ABSOLUTE_PROTOCOL_WITH_WILDCARDS => $entire_uri_without_queries,
            Route::ROUTE_TYPE_ABSOLUTE_DOMAIN =>                  '//'.$request->getHost().$request->getPathInfo(),	// Compare with just the domain and pathinfo
            Route::ROUTE_TYPE_ABSOLUTE_DOMAIN_WITH_WILDCARDS =>   '//'.$request->getHost().$request->getPathInfo(),
            Route::ROUTE_TYPE_ABSOLUTE_PATH =>                    $request->getPathInfo(), // Compare with just the path info
            Route::ROUTE_TYPE_ABSOLUTE_PATH_WITH_WILDCARDS =>     $request->getPathInfo()
        );

        return $return;
    }
}
