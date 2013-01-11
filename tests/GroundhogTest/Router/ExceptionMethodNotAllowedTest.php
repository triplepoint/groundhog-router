<?php

namespace GroundhogTest\Router;

use Groundhog\Router\ExceptionMethodNotAllowed;

class ExceptionMethodNotAllowedTest extends \PHPUnit_Framework_TestCase
{
    public function testgetAllowedMethods()
    {
        $exception = new ExceptionMethodNotAllowed(
            array('context' => 'array')
        );

        $this->assertSame(array('context' => 'array'), $exception->getAllowedMethods());
    }
}
