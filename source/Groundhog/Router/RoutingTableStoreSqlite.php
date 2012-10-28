<?php
namespace Groundhog\Router;

use \SQLite3;

class RoutingTableStoreSqlite implements RoutingTableStoreInterface
{
    /**
     * Where is the config file that holds the cached routing table?
     *
     * @var string
     */
    private $routing_table_file;

    /**
     * The time to live for cached routing tables
     *
     * @var integer
     */
    private $ttl;

    /**
     *
     * @param string  $routing_table_file the path to the sqlite database file
     * @param integer $ttl                The cached routing table's time to live
     *
     * @throws Exception when the SQlite3 extension isn't loaded
     *
     * @return void
     */
    public function __construct($routing_table_file, $ttl = 28800)
    {
        if (!extension_loaded('sqlite3')) {
            throw new Exception('The SQLite3 extension is required for this Routing Table Store to function.');
        }

        $this->routing_table_file = $routing_table_file;
        $this->ttl                = $ttl;
    }

    public function getStoreTtl()
    {
        return $this->ttl;
    }

    public function storeNeedsRebuilding()
    {
        return ( !is_file($this->routing_table_file) || (time() - filemtime($this->routing_table_file)) > $this->getStoreTtl() );
    }

    public function saveRoutingTable(array $routes)
    {
        // Attempt to create the routing table sqlite database table, if it doesn't already exist
        $db = new SQLite3($this->routing_table_file);
        $db->exec('BEGIN;');

        @$db->exec(
            'CREATE TABLE routing_table (
            route_type integer,
            route_http_method text,
            route_regex text,
            class_name text,
            parameter_order text,
            raw_route_string text);'
        );

        // Remove all the auto-generated routes
        if ( ! $db->exec("DELETE FROM routing_table;") ) {
            throw new Exception("Trouble truncating routing table: ".$db->lastErrorMsg());
        }

        // For each detected route, determine if it exists already in the database, and if not, save it
        foreach ($routes as $route) {

            $result = $db->query(
                'SELECT * FROM routing_table WHERE '.
                "route_http_method='".$db->escapeString($route->getRouteHttpMethod()).
                "' AND route_regex='".$db->escapeString($route->getRouteRegex()).
                "' AND route_type='".$db->escapeString($route->getRouteType())."';"
            );
            if ( ($arr_result = $result->fetchArray(SQLITE3_ASSOC))!==false ) {
                throw new Exception(
                    "This handler ({$route->getClassName()}) cannot handle this route ".
                    "({$route->getRouteHttpMethod()} {$route->getRawRouteString()}) because that route's regex ".
                    "({$route->getRouteRegex()}) is already handled by another handler ".
                    "({$arr_result['class_name']})"
                );
            }

            $route_values = array(
                'route_type'        => $route->getRouteType(),
                'route_http_method' => $route->getRouteHttpMethod(),
                'route_regex'       => $route->getRouteRegex(),
                'class_name'        => $route->getClassName(),
                'parameter_order'   => $route->getParameterOrder(),
                'raw_route_string'  => $route->getRawRouteString()
            );

            array_map(
                function ($value) use ($db) {
                    return $db->escapeString($value);
                },
                $route_values
            );

            $db->exec("INSERT INTO routing_table (".implode(', ', array_keys($route_values)).") VALUES ('".implode("', '", array_values($route_values))."');");
        }

        // Commit the changes
        $db->exec('COMMIT;');
    }

    public function getRoutingTable($query_string = '')
    {
        // Connect to the sqlite database
        $db = new SQLite3($this->routing_table_file, SQLITE3_OPEN_READONLY);

        // Determine the query
        $where_clause = '';
        if ( !empty($query_string) ) {
            $where_clause = " WHERE ";
            foreach (explode(' ', $query_string) as $query_fragment) {
                $where_clause .= "( ";
                $where_clause .= " raw_route_string LIKE '%".$db->escapeString($query_fragment)."%' OR ";
                $where_clause .= " class_name LIKE '%".$db->escapeString($query_fragment)."%' OR ";
                $where_clause .= " route_http_method LIKE '%".$db->escapeString($query_fragment)."%' ";
                $where_clause .= ") AND ";
            }
            $where_clause = rtrim($where_clause, ' AND');
        }
        $query = "SELECT * FROM routing_table $where_clause;";


        // Load all the routing table records
        $result = $db->query($query);
        $routing_table = array();
        while ( $arr_result = $result->fetchArray(SQLITE3_ASSOC) ) {
            $routing_table[] = new Route(
                $arr_result['route_type'],
                strtoupper($arr_result['route_http_method']),
                $arr_result['route_regex'],
                $arr_result['class_name'],
                $arr_result['parameter_order'],
                $arr_result['raw_route_string']
            );
        }

        return $routing_table;
    }

    public function findMatchingRoute(RequestInterface $request)
    {
        // Build the SQLite database object
        $db = new SQLite3($this->routing_table_file, SQLITE3_OPEN_READONLY);

        // Create a regular expression function in SQLite that determines whether the first parameter matches the regex in the second parameter
        $db->createFunction(
            'IS_REGEX_MATCH',
            function ($str, $regex) {
                return preg_match($regex, $str) ?  1 : 0;
            },
            2
        );

        // Map the route types to their possible regex match strings, for this request
        //  Note that relative routes will never reach this point, since all full routes are absolute in some sense.
        $arr_match_types = Route::getPathsByRouteType($request);

        // Build the where clause
        $arr_route_path_where_clause = array();
        foreach ($arr_match_types as $route_type => $route_match) {
            $arr_route_path_where_clause[] = " (route_type = $route_type AND IS_REGEX_MATCH('$route_match', route_regex) )";
        }
        $where_clause = "WHERE route_http_method='{$request->getMethod()}' ".
            "AND (".implode(' OR ', $arr_route_path_where_clause).")";

        // Execute the query and fetch the result
        $result = $db->query("SELECT * FROM routing_table $where_clause ORDER BY route_type ASC LIMIT 1;");
        $arr_match = $result->fetchArray(SQLITE3_ASSOC);

        // if there was no perfect match, we need to find out if the URL itself was legit, in order to know whether to assemble a 405 response, or a 404 response
        if ( empty($arr_match) ) {

            // Determine if there were any matches at all for this URL, with any HTTP method
            $error_result = $db->query("SELECT route_http_method FROM routing_table WHERE (".implode(' OR ', $arr_route_path_where_clause).");");

            // Fetch the set of allowed methods (if any) on this route
            $arr_allowed_methods = array();
            while ($arr_result = $error_result->fetchArray(SQLITE3_ASSOC) ) {
                $arr_allowed_methods[] = $arr_result['route_http_method'];
            }

            if ( !empty($arr_allowed_methods) ) {
                // If there's a match on this URL, just not for the given HTTP method, return a 405
                throw new ExceptionMethodNotAllowed($arr_allowed_methods);

            } else {
                // Else this URL has no resource at all.  Return a 404
                throw new ExceptionNotFound();
            }
        }

        // Return the routing table response, suitable for calling the handler
        return new Route(
            $arr_match['route_type'],
            $arr_match['route_http_method'],
            $arr_match['route_regex'],
            $arr_match['class_name'],
            $arr_match['parameter_order'],
            $arr_match['raw_route_string']
        );
    }
}
