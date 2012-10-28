<?php

namespace Groundhog\Router\Tests;

use Groundhog\Router\Route;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersReturnProperConstructorParameters()
    {
        $object = new Route(Route::ROUTE_TYPE_ABSOLUTE_PROTOCOL, 'GET', '/.*/', 'Class_Name', array(2, 1, 3), '/route/string');

        $this->assertSame(Route::ROUTE_TYPE_ABSOLUTE_PROTOCOL, $object->getRouteType());
        $this->assertSame('GET',           $object->getRouteHttpMethod());
        $this->assertSame('/.*/',          $object->getRouteRegex());
        $this->assertSame('Class_Name',    $object->getClassName());
        $this->assertSame(array(2, 1, 3),  $object->getParameterOrder());
        $this->assertSame('/route/string', $object->getRawRouteString());
    }

    public function testExtractParametersFromRequest()
    {
        $this->markTestIncomplete('Not yet implemented');
    }

    public function testGetPathsByRouteType()
    {
        $this->markTestIncomplete('Not yet implemented');
    }
}
