# Groundhog Router

[![Build Status](https://travis-ci.org/triplepoint/groundhog-router.png?branch=master)](https://travis-ci.org/triplepoint/groundhog-router)

## Introduction
This library provides a basic router which can interpret incoming requests, determine to what class the request maps, and return the action handler class ready for execution.

Dependencies are kept to zero, with interfaces provided for extension points.

## Basic Structure
There are 3 core components in this library: the Router, the Route Parser, and the Routing Table Store.  There are several secondary elements that get passed around as messages or used as helpers: RequestInterface,
Route, and ExceptorInterface.  Finally, the end result is some object that implements RouteHandlerInterface.

### Router
The Router takes in an object that represents the incoming request and which implements RequestInterface.  This request is then delegated to the Routing Table Store in an attempt to find a matching Route.  Once
the Route is found, the Router constructs the appropriate object that implements RouteHandlerInterface, which is a Controller in typical framework terminology.

### Route Parser
The Route Parser is any object that implements RouteParserInterface.  It's responsibility is to acquire the set of Routes to which the project can respond.  This is intentionally abstract - the included Route Parser operates
on a set of phpdoc attributes to determine the Routes, but any strategy of route encoding could be used, with an appropriate Route Parser written to interpret it.

### Routing Table Store
The Routing Table Store implements RoutingTableStoreInterface, and represents a cache in which to store the routing table once the Route Parser generates the set of Routes.  There are Routing Table Stores
included to support APC and SQLite, and a special "NoCache" Store which does not cache at all and instead prompts the Route Parser to always regenerate the routing table.  Alternative storage mechanisms can
easily be added by implmenting new objects against RoutingTableStoreInterface.

### RequestInterface
Objects that implement the RequestInterface represent the incoming request.  There generally need only be one of these implemented, and in an attempt to remain independant of other libraries, 
it is left to the user to implement this object.

### Route
This object represents a single route rule, and is used as a messenger between the Router, Route Parser, and Routing Table Store.

### ExceptorInterface
Objects that implement this interface are responsible for being delegated to for generating exceptions.  This allows the developer to swap in external exceptions.

### RouteHandlerInterface
These objects are the traditional controllers in MVC architecture.  In an attempt to contain dependencies while allowing for testing, these objects can announce their preferred dependency injection container
which is then passed in to their constructors.  Also, these objects are loaded by the Router with any incoming request's call parameters that may be present.

## Example
First, lets define some classes that must be implemented:
``` php
<?php
// ### Exceptor.php ###

namespace MyProject;

use Groundhog\Router\ExceptorInterface;

class Exceptor implements ExceptorInterface
{
    public function exception($message = '', $code = 0, \Exception $previous = null)
    {
        return new \Exception($message, $code, $previous);
    }

    public function httpException( $private_message = '', $http_status_code = 0, array $additional_headers = array(), $public_message = null, \Exception $previous = null )
    {
        return new \Exception($private_message, $http_status_code, $previous);
    }
}

```

``` php
<?php
// ### HttpRequestWrapper.php ###

<?php
namespace MyProject;

use \Symfony\Component\HttpFoundation\Request;
use \Groundhog\Router\RequestInterface;

class HttpRequestWrapper implements Router\RequestInterface
{
    protected $request;

    public function __construct( Request $request )
    {
        $this->request = $request;
    }

    public function getPathInfo()
    {
        return $this->request->getPathInfo();
    }

    public function getUri()
    {
        return $this->request->getUri();
    }

    public function getHost()
    {
        return $this->request->getHost();
    }

    public function getMethod()
    {
        return $this->request->getMethod();
    }
}

```

``` php
<?php
// ### SimpleRouteHandler.php ###

namespace MyProject;

use \Groundhog\Router\RouteHandlerInterface;
use \Groundhog\Router\RouteHandlerServiceContainerInterface;

class SimpleRouteHandler implements RouteHandlerInterface
{
    /**
     * This dependency will be provided by the service container returned from getDefaultServiceContainer() 
     */
    protected $some_dependency;

    /**
     * this call parameter will be present in the HTTP request, and will be passed in an array by the Router
     */
    protected $some_request_parameter;

    static public function getDefaultServiceContainer()
    {
        return new ServiceContainer();
    }

    public function __construct( RouteHandlerServiceContainerInterface $service_container = null )
    {
        $this->some_dependency = $service_container['some_dependency'];
    }

    /**
     * Route handlers are required to accept call parameters, even if they don't need them.
     *
     * @see Groundhog\Router\RouteHandlerInterface::setCallParameters()
     */
    public function setCallParameters(array $call_parameters)
    {
        $this->some_request_parameter = $call_parameters['some_request_parameter'];
    }

    /**
     * In this example, we've chosen the convention of all our Route Handlers use the execute() method to perform their controller action.
     *
     * The Annotations here are the ones that the RouteParserAnnotation implementation of the RouteParserInterface is designed to detect.
     *
     * !HttpRoute GET //www.mysite.com/some_route
     */
    public function execute()
    {
        echo "Hello World!";
        echo "for some_request_parameter, you provided:". $this->some_request_parameter;
    }
}
```

``` php
<?php
// ### ServiceContainer.php ###

namespace MyProject;

use \Groundhog\Router\RouteHandlerServiceContainerInterface;
use \Pimple

class ServiceContainer extends Pimple implements RouteHandlerServiceContainerInterface
{
    public function __construct()
    {
        parent::__construct();

        $this['some_dependency'] = function ($c) {
            // This dependency is destined to end up in the Route Handler's $some_dependency property
            return new SimpleXmlElement(); 
        };
    }
}
```

Now that these classes are defined, we can set up the router and use it.

``` php
<?php
// ### index.php ###
 
// This exceptor will be a dependency which handles generating exceptions for the objects below. 
$exceptor = new \MyProject\Exceptor();

// Here we're writing a thin wrapper around Symfony's HttpFoundation\Request object to implement RequestInterface.
$symfony_request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$request = new \MyProject\HttpRequestWrapper($symfony_request);

// The routing Table Store we're using here is the simple "NoCache" store which provides no caching ability.  It's convenient for development.
$routing_table_store = new \Groundhog\Router\RoutingTableStoreNoCache($exceptor);

// The route parser here is the one which reads annotations.  It is being asked to start in the 'library' directory to search for classes with annotations. 
$parser = new \Groundhog\Router\RouteParserAnnotation('library', $exceptor);

// The Router takes in all these elements as dependencies
$router = new \Groundhog\Router\Router();
$router->route_parser  = $parser;
$router->routing_table = $routing_table_store;
$router->request       = $request;


// Command the router to find the appropriate Route Handler for the request it was given and configure it against the request.
$route_handler = $router->getRouteHandler();

// Command the returned Route Handler to perform its route-handling action
$route_handler->execute();
```

In practice, a lot of the creation and configuration of the various dependencies can be moved into a depdency container, leaving a clean startup in index.php.
