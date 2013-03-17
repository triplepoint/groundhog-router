<?php
namespace Groundhog\Router;

class RouteParserAnnotation implements RouteParserInterface
{
    /**
     * Where do we look for the handlers?
     *
     * Note that this is a recursive crawl, and any concrete class found that
     * implements the requst handler interface will be evaluated for its routes.
     *
     * @var string
     */
    private $realm_root_path;

    /**
     *
     * @param string $realm_root_path The path in which to look for the route handlers (recursive)
     */
    public function __construct($realm_root_path)
    {
        $this->realm_root_path = $realm_root_path;
    }

    /**
     * Rebuild the routing table
     *
     * Route Handler classes can have RouteBase annotations, and methods can have Route annotations.  For
     * relative method Routes, the class RouteBases are prepended to create a set of full Routes
     * for that method. For absolute method Routes, they become full routes by themselves.  Thus,
     * the routing table generated here will always be a set of full absolute routes.
     *
     * @return Route[] The collection of Route objects that make up the routing table.
     */
    public function buildRouteTable()
    {
        $this->includeAllPhpFiles($this->realm_root_path);

        $route_handlers = $this->fetchAllDefinedConcreteClassesOfType('Groundhog\Router\RouteHandlerInterface');

        $routing_table = array();

        foreach ($route_handlers as $class_name) {

            $class_reflect = new \ReflectionClass($class_name);

            $execute_method_reflect = $class_reflect->getMethod('execute');

            foreach ($this->extractRoutesFromMethodHeader($execute_method_reflect->getDocComment()) as $arr_method_route) {

                // Trim off any trailing slashes (TODO in the future, this will likely be modified to allow distinctions between with-trailing-slash and without)
                $arr_method_route['request_route'] = rtrim($arr_method_route['request_route'], '/');

                // Generate a regular expression that would match this extracted route
                list($route_regex, $arr_parameter_order) = $this->generateRegexFromRoute($arr_method_route['request_route']);

                $routing_table[] = new Route(
                    $this->getRouteType($arr_method_route['request_route']),
                    $arr_method_route['request_method'],
                    $route_regex,
                    $class_name,
                    serialize($arr_parameter_order),
                    $arr_method_route['request_route']
                );
            }
        }

        return $routing_table;
    }

    /**
     * Include all the files in the given directory with the appropriate extension, recursively.
     *
     * This ensures that the classes defined in the files will be visible in get_defined_classes();
     *
     * Needless to say, any non-class files with executable code will also be run.  Plan accordingly.
     *
     * @param string $root_path The root path from which to start
     *
     * @return void
     */
    private function includeAllPhpFiles($root_path)
    {
        // Loop through the target path and include any class files discovered
        foreach (new \DirectoryIterator($root_path) as $file_in_dir) {

            if ($file_in_dir->isDot()) {
                continue;

            } else if ($file_in_dir->isDir()) {
                $this->includeAllPhpFiles($file_in_dir->getPathName());

            } else if ($file_in_dir->getExtension() == 'php') {
                include_once $file_in_dir->getPathName();
            }
        }
    }

    /**
     * Find all class files in the given root path which implement the given interface
     *
     * @param string $interface the interface in question
     *
     * @return array
     */
    private function fetchAllDefinedConcreteClassesOfType($interface)
    {
        $declared_classes = get_declared_classes();

        $result = array();
        foreach ($declared_classes as $class_name) {
            if (!($reflect = new \ReflectionClass($class_name))) {
                continue;
            }

            if ($reflect->isAbstract()) {
                continue;
            }

            if (!in_array($interface, $reflect->getInterfaceNames())) {
                continue;
            }

            $result[] = $class_name;
        }

        return $result;
    }

    /**
     * Given a comment block string, evaluate the comment block for potential Route annotations.
     * Routes are returned in an array, where each array element is an array with a 'request_method'
     * (which would be the HTTP request method, like GET or PUT), and a 'request_route' index (which
     * would be the actual route string).
     *
     * Note that if a NoRoute annotation is found in the comment block, no routes will be returned,
     * making this method unreachable by a page request.
     *
     * @param string $method_comment the comment block from the method in question.
     *
     * @return array the method's routes, each of the form <code>array('request_method'=>X, 'request_route'=>Y)</code>
     */
    private function extractRoutesFromMethodHeader($method_comment)
    {
        // If the method has a !NoRoute annotation then its marked for being skipped.  Return an empty array.
        if (preg_match('/\!NoRoute/i', $method_comment)) {
            return array();
        }

        preg_match_all('/  \!HttpRoute  [ \t]+  (?P<request_method>[A-Za-z]+)  [ \t]*  (?P<request_route>[^ ]*)  [ \t]*  $/imx', $method_comment, $regex_routes, PREG_SET_ORDER);

        $arr_method_routes = array();
        if (!empty($regex_routes)) {
            foreach ($regex_routes as $match) {
                $arr_method_routes[] = array(
                    'request_method' => $match['request_method'],
                    'request_route'  => $match['request_route']
                );
            }
        }

        return $arr_method_routes;
    }

    /**
     * There are several different degrees of route relative-ness.  Determine that
     * from the given route.
     *
     * These route types have an implied order-of-precedence, with the more specific routes
     * being of higher precedence.  Routes with wildcards take a lower precedence than they would otherwise.
     *
     * @param string $route_string
     *
     * @throws Exception when the route type isn't determinable
     *
     * @return integer the route type
     */
    private function getRouteType($route_string)
    {
        // Does the route have wildcards?
        $has_wildcards = (boolean) preg_match('/[#$]{1}([0-9])+/', $route_string);

        if (substr($route_string, 0, 2) == '//') {
            // Specific domain, unspecified protocol, absolute path info
            $match_path = $has_wildcards ? Route::ROUTE_TYPE_ABSOLUTE_DOMAIN_WITH_WILDCARDS : Route::ROUTE_TYPE_ABSOLUTE_DOMAIN;

        } else if (substr($route_string, 0, 1) == '/') {
            // Absolute path, relative to the domain root
            $match_path = $has_wildcards ? Route::ROUTE_TYPE_ABSOLUTE_PATH_WITH_WILDCARDS : Route::ROUTE_TYPE_ABSOLUTE_PATH;

        } else if (strpos($route_string, '://') !== false) {
            // Specific domain, specific protocol, absolute paths
            $match_path = $has_wildcards ? Route::ROUTE_TYPE_ABSOLUTE_PROTOCOL_WITH_WILDCARDS : Route::ROUTE_TYPE_ABSOLUTE_PROTOCOL;

        } else {
            throw new Exception('Given route string ('.$route_string.') does not match any of the allowed route types.');
        }

        // Return the result
        return $match_path;
    }

    /**
     * Build a regular expression for the route, sufficient to match incoming path-info.
     * Also, determine the numerical order of the parameters as they appear in the route.
     *
     * This is used to determine if the request for a given page request matches a given route.
     *
     * @param string $route_string
     *
     * @return array contains the regular expression and the array of order of the parameters
     */
    private function generateRegexFromRoute($route_string)
    {
        // Extract the order of the parameter tags in the route (for instance, /some/#2/route/#3/#1 would have array(2,3,1)  )
        preg_match_all('/[#$]{1}([0-9])+/', $route_string, $arr_matches, PREG_PATTERN_ORDER);
        $arr_parameter_order = $arr_matches[1];

        // Build an appropriate regex, by replacing the '$1' or '#4' bits of the route with the appropriate character classes
        $route_string = str_replace('\$', '$', preg_quote($route_string, '/')); // Fix the unwanted escaping of $ in the route
        $regex = preg_replace(array('/[$]{1}[0-9]+/', '/[#]{1}[0-9]+/'), array('([^#?\/\\\\\]+)', '([0-9]+)'), $route_string);

        // Append the "optional /" regex, if the regex isn't just a slash
        // TODO this assumption about trailing slashes may not be a permenant thing
        $regex .= ($regex != '\/') ? '\/{0,1}' : '';

        // Wrap the regex in the appropriate regex bits
        $regex = "/^". $regex . "$/";

        // Return the generated regex
        return array($regex, $arr_parameter_order);
    }
}
