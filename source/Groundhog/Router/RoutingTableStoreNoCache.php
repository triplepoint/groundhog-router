<?php
namespace Groundhog\Router;

/**
 * This class encapsulates the routing table.
 *
 * This routing table strategy has no cache, and instead simply
 * forces a rebuild on every request.  Needless to say, this
 * is not the most efficient strategy, and is generally only useful
 * for testing.
 */
class RoutingTableStoreNoCache implements RoutingTableStoreInterface
{
    private $stored_routing_table;

    /**
     * The exception generator.
     *
     * @var ExceptorInterface
     */
    private $exceptor;

    public function __construct(ExceptorInterface $exceptor)
    {
        $this->exceptor = $exceptor;
    }

    public function getStoreTtl()
    {
        return 0;
    }

    public function storeNeedsRebuilding()
    {
        return true;
    }

    public function saveRoutingTable(array $routes)
    {
        $this->stored_routing_table = $routes;
    }

    public function getRoutingTable($query_string = '')
    {
        $routing_table = array();

        foreach ($this->stored_routing_table as $route) {
            $is_search_match = null;
            foreach (explode(' ', $query_string) as $query_fragment) {
                if ( preg_match('/.*'.$query_fragment.'.*/i', $route->getRawRouteString()) ||
                     preg_match('/.*'.$query_fragment.'.*/i', $route->getClassName()) ||
                     preg_match('/.*'.$query_fragment.'.*/i', $route->getRouteHttpMethod())
                ) {
                    $is_search_match = true;
                } else {
                    $is_search_match = false;
                    break;
                }
            }

            if ($is_search_match) {
                $routing_table[] = $route;
            }
        }

        return $routing_table;
    }

    public function findMatchingRoute(RequestInterface $request)
    {
        $arr_match_types = Route::getPathsByRouteType($request);

        $arr_allowed_methods = array();

        foreach ($this->stored_routing_table as $route) {

            foreach ($arr_match_types as $route_type => $route_match) {
                if ( $route->getRouteHttpMethod() == $request->getMethod() && $route->getRouteType() == $route_type && preg_match($route->getRouteRegex(), $route_match)) {
                    return $route;

                } else if ( $route->getRouteType() == $route_type && preg_match($route->getRouteRegex(), $route_match)) {
                    $arr_allowed_methods[] = $route->getRouteHttpMethod();
                }
            }
        }

        // No match found
        if ( !empty($arr_allowed_methods) ) {
            // If there's a match on this URL, just not for the given HTTP method, return a 405
            throw $this->exceptor->httpException(null, 405, array('Allow' => implode(', ', $arr_allowed_methods)));

        } else {
            // Else this URL has no resource at all.  Return a 404
            throw $this->exceptor->httpException(null, 404);
        }
    }
}
