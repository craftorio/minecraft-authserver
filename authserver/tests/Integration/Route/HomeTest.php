<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Tests\Integration\Route;

use Craftorio\Authserver\Route\Home;
use Craftorio\Authserver\Tests\Support\AppTestHarness;
use PHPUnit\Framework\TestCase;

final class HomeTest extends TestCase
{
    /** @var AppTestHarness */
    private $harness;

    protected function setUp(): void
    {
        $this->harness = new AppTestHarness();
    }

    protected function tearDown(): void
    {
        $this->harness->destroy();
    }

    public function testHomeReturnsNull(): void
    {
        $route = new Home();
        $request = $this->harness->makeRequest('GET', '/');
        $response = $this->harness->invokeRoute($route, $request);

        $this->assertSame(200, $response['status']);
        $this->assertNull($response['json']);
    }
}
