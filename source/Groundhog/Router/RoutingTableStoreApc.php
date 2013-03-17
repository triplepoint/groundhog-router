<?php
namespace Groundhog\Router;

/**
 * This class encapsulates the routing table.
 *
 * This routing table strategy stores the routing table in APC.
 */
class RoutingTableStoreApc implements RoutingTableStoreInterface
{
    /**
     * The index used to store the routing table in APC
     *
     * @var string
     */
    const APC_CACHE_INDEX = 'GROUNDHOG_ROUTING_TABLE';

    /**
     * The time to live for cached routing tables
     *
     * @var integer
     */
    private $ttl;

    /**
     * Require that the APC extension be present before this Table Store can be used.
     *
     * @param integer $ttl The cached routing table's time to live
     *
     * @throws Exception when the APC extension isn't loaded.
     *
     * @return void
     */
    public function __construct($ttl = 28800)
    {
        if (!extension_loaded('apc')) {
            throw new Exception('The APC extension is required for this Routing Table Store to function.');
        }

        $this->ttl      = $ttl;
    }

    public function getStoreTtl()
    {
        return $this->ttl;
    }

    public function storeNeedsRebuilding()
    {
        return ! apc_exists(self::APC_CACHE_INDEX);
    }

    public function saveRoutingTable(array $routes)
    {
        apc_store(self::APC_CACHE_INDEX, $routes, $this->getStoreTtl());
    }

    public function getRoutingTable($query_string = '')
    {
        $stored_routing_table = apc_fetch(self::APC_CACHE_INDEX);
        if (!is_array($stored_routing_table)) {
            $stored_routing_table = array();
        }

        $routing_table = array();

        foreach ($stored_routing_table as $route) {
            $keeper = null;
            foreach (explode(' ', $query_string) as $query_fragment) {
                if (preg_match('/.*'.$query_fragment.'.*/i', $route->getRawRouteString()) ||
                     preg_match('/.*'.$query_fragment.'.*/i', $route->getClassName()) ||
                     preg_match('/.*'.$query_fragment.'.*/i', $route->getRouteHttpMethod())
                ) {
                    $keeper = true;
                } else {
                    $keeper = false;
                    break;
                }
            }
            if ($keeper) {
                $routing_table[] = $route;
            }
        }

        return $routing_table;
    }

    public function findMatchingRoute(RequestInterface $request)
    {
        $arr_match_types = Route::getPathsByRouteType($request);

        $stored_routing_table = apc_fetch(self::APC_CACHE_INDEX);
        if (!is_array($stored_routing_table)) {
            $stored_routing_table = array();
        }

        $arr_allowed_methods = array();

        foreach ($stored_routing_table as $route) {

            foreach ($arr_match_types as $route_type => $route_match) {
                if ($route->getRouteHttpMethod() == $request->getMethod() && $route->getRouteType() == $route_type && preg_match($route->getRouteRegex(), $route_match)) {
                    return $route;

                } else if ($route->getRouteType() == $route_type && preg_match($route->getRouteRegex(), $route_match)) {
                    $arr_allowed_methods[] = $route->getRouteHttpMethod();
                }
            }
        }

        // No match found
        if (!empty($arr_allowed_methods)) {
            // If there's a match on this URL, just not for the given HTTP method, return a 405
            throw new ExceptionMethodNotAllowed($arr_allowed_methods);

        } else {
            // Else this URL has no resource at all.  Return a 404
            throw new ExceptionNotFound();
        }
    }
}
