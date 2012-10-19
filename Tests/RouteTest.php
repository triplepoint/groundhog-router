<?php

namespace Groundhog\Router\Tests;

use Groundhog\Router\Route;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiationThrowsNoExceptions()
    {
        try {
            $object = new Route(Route::ROUTE_TYPE_ABSOLUTE_PROTOCOL, 'GET', '/.*/', 'Class_Name', array(2, 1, 3), '/route/string');

        } catch (Exception $e) {
            $this->fail('Exception thrown during instantiation:'. $e->getMessage());

        }
    }

    public function testGetRouteTypeReturnsInstantiationRoute()
    {
        $object = new Route(Route::ROUTE_TYPE_ABSOLUTE_PROTOCOL, 'GET', '/.*/', 'Class_Name', array(2, 1, 3), '/route/string');

        $this->assertSame(Route::ROUTE_TYPE_ABSOLUTE_PROTOCOL, $object->getRouteType());
    }
}
