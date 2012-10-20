<?php
namespace Groundhog\Router;

use \Exception;

/**
 * Classes that implement this interface represent data stores
 * for caching the routing table.
 */
interface RoutingTableStoreInterface
{
    /**
     * Get the store's TTL in seconds
     *
     * @return integer the time to live of the route store
     */
    public function getStoreTtl();

    /**
     * Does the routing table store need to be rebuilt?
     *
     * @return boolean
     */
    public function storeNeedsRebuilding();

    /**
     * Save the passed routing table.
     *
     * @param Groundhog\Router\Routes[] $routes an array of Route objects
     *
     * @throws Exception if something goes wrong
     *
     * @return void
     */
    public function saveRoutingTable(array $routes);

    /**
     * Fetch the routing table as an array of Routes.
     *
     * This is largely used for reporting.
     *
     * @param string $query_string An optional search parameter
     *
     * @return array
     */
    public function getRoutingTable($query_string = '');

    /**
     * Given a request object, attempt to find a matching route and return
     * a Route describing that route.
     *
     * @param RequestInterface $request
     *
     * @throws Exception if the route is not found
     * @throws Exception if the route is found but the HTTP method is not supported
     *
     * @return Route
     */
    public function findMatchingRoute(RequestInterface $request);
}
