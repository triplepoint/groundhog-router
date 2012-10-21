<?php
namespace Groundhog\Router;

/**
 * Classes which implement this interface are the controllers
 * which handle an route request.  Route Handlers have a minimal
 * set of requirements (as evidenced by the sparse interface below).
 *
 * Route Handlers must accept the call parameters that were present
 * in the route, even if they don't need them or the route doesn't
 * include any.
 *
 * In addition, all Route Handlers provide their own default service
 * container, which should be passed into the constructor.  This allows
 * for testability, without requiring a global dependency container.
 *
 * The Route Handler is instructed to perform its actions by calling
 * the execute() method.
 */
interface RouteHandlerInterface
{
    /**
     * Fetch the default service container to use when instantiating route handlers of this type
     * This allows the route handler to define its own dependencies without necessarily
     * hardwiring them in in the constructor.
     *
     *  @return RouteHandlerServiceContainerInterface|null
     */
    public static function getDefaultServiceContainer();

    /**
     * The constructor accepts the service container, and it's up to the route handler
     * to know what to do with it.
     *
     * @param RouteHandlerServiceContainerInterface $service_container
     *
     * @return void
     */
    public function __construct(RouteHandlerServiceContainerInterface $service_container = null);

    /**
     * Set the call parameters (if any) that describe the request the be handled
     *
     * @param array $call_parameters
     *
     * @return void
     */
    public function setCallParameters(array $call_parameters);
}
