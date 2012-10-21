<?php
namespace Groundhog\Router;

/**
 * The Router is responsible for routing page requests to the appropriate handler code.
 *
 * Incoming HTTP requests need to be mapped to the appropriate handler code in a way that is optimized for
 * speed and which is also easy to work with.
 *
 * This class's getRouteHandler() method coordinates with the routing table and route parser objects in order
 * to identify the appropriate route handler to which to pass the request.
 */
class Router
{
    /**
     * The routing table handler to use to read and store the routing table data
     *
     * @var RoutingTableStoreInterface
     */
    public $routing_table;

    /**
     * The parsing handler that will extract routes for the project
     *
     * @var RouteParserInterface
     */
    public $route_parser;

    /**
     * The request with which to route
     *
     * @var RequestInterface
     */
    public $request;

    /**
     * Find and return the appropriate route handler for this request
     *
     * If the routing table store decides that the routing table first needs
     * to be rebuilt, use the route parser to rebuild the table before evaluating
     * routes.
     *
     * @return RouteHandlerInterface
     */
    public function getRouteHandler()
    {
        if ($this->routing_table->storeNeedsRebuilding()) {
            $routes = $this->route_parser->buildRouteTable();
            $this->routing_table->saveRoutingTable($routes);
        }

        $route = $this->routing_table->findMatchingRoute($this->request);

        $route_handler_class_name  = $route->getClassName();
        $call_parameters = $route->extractParametersFromRequest($this->request);

        $route_handler_service_container = $route_handler_class_name::getDefaultServiceContainer();
        $route_handler = new $route_handler_class_name($route_handler_service_container);
        $route_handler->setCallParameters($call_parameters);

        return $route_handler;
    }
}
