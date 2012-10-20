<?php
namespace Groundhog\Router;

interface RouteParserInterface
{
    /**
     * Build the routing table.
     *
     * @return Route[] The collection of Route objects that make up the routing table.
     */
    public function buildRouteTable();
}
