# Groundhog Router

[![Build Status](https://travis-ci.org/triplepoint/groundhog-router.png?branch=master)](https://travis-ci.org/triplepoint/groundhog-router)

## Introduction
This library provides a request router which can interpret incoming requests, determine to what class the request maps, and return the action handler class ready for execution.

Dependencies are kept to zero, with interfaces provided for extension points.

## Disclaimer
While I take great care to produce code that is free of excess dependencies and short-sighted assumptions, I feel I should warn you
that this code is *not* primarily meant for public consumption.  In other words, please understand that if you want to use this code
in your own work you're free to do so under the provided license, but I'm not promising that the API will be stable or that the code 
will necessarily meet your needs out of the box.

So please, feel free to fork this code and adapt it to your own needs - or even better, offer comments on how I could improve the 
general-purpose nature of the code.  But also accept that at the end of the day, this really is just a starting place for your own work. 

## Basic Structure
There are 3 core components in this library: the Router, a Route Parser, and a Routing Table Store.  There are several secondary elements that get passed around as messages or used as helpers: RequestInterface,
Route, etc.  Finally, the end result is some object that implements RouteHandlerInterface.

### Router
The Router takes in an object that represents the incoming request and which implements RequestInterface.  This request is then delegated to the Routing Table Store in an attempt to find a matching Route.  Once
the Route is found, the Router constructs the appropriate object that implements RouteHandlerInterface, which is a Controller in typical framework terminology.

### Route Parser
A Route Parser is any object that implements RouteParserInterface.  It's responsibility is to acquire the set of Routes to which the project can respond.  This is intentionally abstract - the included RouteParserAnnotation 
Route Parser operates on a set of phpdoc attributes to determine the Routes, but any strategy of route encoding could be used, with an appropriate Route Parser written to interpret it.

### Routing Table Store
A Routing Table Store implements RoutingTableStoreInterface, and represents a cache in which to store the routing table once the Route Parser generates the set of Routes.  There are Routing Table Stores
included to support APC and SQLite, and a special "NoCache" Store which does not cache at all and instead prompts the Route Parser to always regenerate the routing table.  Alternative storage mechanisms can
easily be added by implmenting new objects against RoutingTableStoreInterface.

### RequestInterface
Objects that implement the RequestInterface represent the incoming request.  There generally need only be one of these implemented, and in an attempt to remain independant of other libraries, 
it is left to the user to implement this object.  The methods defined in RequestInterface are generally based on Symfony's Http-Foundation library, but anything that properly implements this interface is valid.

### Route
This object represents a single route rule, and is used as a messenger container between the Router, Route Parser, and Routing Table Store.

### RouteHandlerInterface
These objects are the traditional controllers in MVC architecture.  In an attempt to contain dependencies while allowing for testing, these objects can announce their preferred dependency injection container
which is then passed back to them for consumption.  Also, these objects are loaded by the Router with any incoming request's call parameters that may be present.

## Example
First, lets define some classes that must be implemented:

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

    public function processServiceContainer(RouteHandlerServiceContainerInterface $service_container = null)
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
        $this->some_request_parameter = $call_parameters[1];
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
 
// Here we're writing a thin wrapper around Symfony's HttpFoundation\Request object to implement RequestInterface.
$symfony_request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$request = new \MyProject\HttpRequestWrapper($symfony_request);

// The routing Table Store we're using here is the simple "NoCache" store which provides no caching ability.  It's convenient for development.
$routing_table_store = new \Groundhog\Router\RoutingTableStoreNoCache();

// The route parser here is the one which reads annotations.  It is being asked to start in the 'source' directory to search for classes with annotations. 
$parser = new \Groundhog\Router\RouteParserAnnotation('source');

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

## API Documentation
Automated API documentation is available at [GitApiDoc](http://gitapidoc.com/api/triplepoint/groundhog-router/).
