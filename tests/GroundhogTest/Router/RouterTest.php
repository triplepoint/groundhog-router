<?php

namespace GroundhogTest\Router;

use Groundhog\Router\Router;
use Groundhog\Router\Route;
use GroundhogTest\Router\Fixtures\RouteHandler;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testRouterRebuildsRouteStoreWhenNecessary()
    {
        $route = new Route(Route::ROUTE_TYPE_ABSOLUTE_PROTOCOL, 'GET', '/.*/', 'GroundhogTest\Router\Fixtures\RouteHandler', array(2, 1, 3), '/route/string');


        $routing_table = $this->getMock('\Groundhog\Router\RoutingTableStoreInterface');

        $routing_table->expects($this->once())
            ->method('storeNeedsRebuilding')
            ->will($this->returnValue(true));

        $routing_table->expects($this->once())
            ->method('saveRoutingTable');

        $routing_table->expects($this->once())
            ->method('findMatchingRoute')
            ->will($this->returnValue($route));


        $route_parser  = $this->getMock('\Groundhog\Router\RouteParserInterface');

        $route_parser->expects($this->once())
            ->method('buildRouteTable')
            ->will($this->returnValue(array()));


        $request       = $this->getMock('\Groundhog\Router\RequestInterface');


        $router = new Router();
        $router->routing_table = $routing_table;
        $router->route_parser  = $route_parser;
        $router->request       = $request;

        $route_handler = $router->getRouteHandler();
    }

    public function testRouterDoesNotRebuildRouteStoreWhenNotNecessary()
    {
        $route = new Route(Route::ROUTE_TYPE_ABSOLUTE_PROTOCOL, 'GET', '/.*/', 'GroundhogTest\Router\Fixtures\RouteHandler', array(2, 1, 3), '/route/string');


        $routing_table = $this->getMock('\Groundhog\Router\RoutingTableStoreInterface');

        $routing_table->expects($this->once())
            ->method('storeNeedsRebuilding')
            ->will($this->returnValue(false));

        $routing_table->expects($this->never())
            ->method('saveRoutingTable');

        $routing_table->expects($this->once())
            ->method('findMatchingRoute')
            ->will($this->returnValue($route));


        $route_parser  = $this->getMock('\Groundhog\Router\RouteParserInterface');

        $route_parser->expects($this->never())
            ->method('buildRouteTable');


        $request       = $this->getMock('\Groundhog\Router\RequestInterface');


        $router = new Router();
        $router->routing_table = $routing_table;
        $router->route_parser  = $route_parser;
        $router->request       = $request;

        $route_handler = $router->getRouteHandler();
    }
}
