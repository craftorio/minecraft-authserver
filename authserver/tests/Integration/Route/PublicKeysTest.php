<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Tests\Integration\Route;

use Craftorio\Authserver\Route\PublicKeys;
use Craftorio\Authserver\Tests\Support\AppTestHarness;
use PHPUnit\Framework\TestCase;

final class PublicKeysTest extends TestCase
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

    public function testPublicKeysResponseShape(): void
    {
        $route = $this->harness->getContainer()->get(PublicKeys::class);
        $request = $this->harness->makeRequest('GET', '/publickeys');
        $response = $this->harness->invokeRoute($route, $request);

        $this->assertSame(200, $response['status']);
        $this->assertArrayHasKey('profilePropertyKeys', $response['json']);
        $this->assertNotEmpty($response['json']['profilePropertyKeys']);
        $this->assertArrayHasKey('publicKey', $response['json']['profilePropertyKeys'][0]);
        $this->assertNotEmpty($response['json']['profilePropertyKeys'][0]['publicKey']);
    }
}
