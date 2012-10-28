<?php

namespace Groundhog\Router\Tests\Fixtures;

use Groundhog\Router\RouteHandlerInterface;
use Groundhog\Router\RouteHandlerServiceContainerInterface;

/**
 * This class stands in for a generic Route Handler, suitable for testing the router.
 *
 * It's not necessary that any of these methods actually work, since the router never calls them.
 *
 */
class RouteHandler implements RouteHandlerInterface
{
    public static function getDefaultServiceContainer()
    {
        return null;
    }

    public function processServiceContainer(RouteHandlerServiceContainerInterface $service_container = null)
    {

    }

    public function setCallParameters(array $call_parameters)
    {

    }
}