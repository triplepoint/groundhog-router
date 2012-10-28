<?php

namespace Groundhog\Router\Tests;

use Groundhog\Router\Exception;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionStoresContextArray()
    {
        $exception = new Exception(
            '',
            array('context' => 'array')
        );

        $this->assertSame( array('context' => 'array'), $exception->getContext());
    }
}
