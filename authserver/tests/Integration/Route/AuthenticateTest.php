<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Tests\Integration\Route;

use Craftorio\Authserver\Route\Authenticate;
use Craftorio\Authserver\Tests\Support\AppTestHarness;
use PHPUnit\Framework\TestCase;

final class AuthenticateTest extends TestCase
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

    public function testMissingFieldsRejected(): void
    {
        $route = $this->harness->getContainer()->get(Authenticate::class);
        $request = $this->harness->makeRequest('POST', '/authenticate', [
            'username' => 'Steve',
        ]);
        $response = $this->harness->invokeRoute($route, $request);

        $this->assertSame(403, $response['status']);
        $this->assertSame('InvalidRequestException', $response['json']['error']);
    }

    public function testValidCredentialsAccepted(): void
    {
        $this->harness->createAccount('Steve', 'secret');
        $route = $this->harness->getContainer()->get(Authenticate::class);
        $request = $this->harness->makeRequest('POST', '/authenticate', [
            'username' => 'Steve',
            'password' => 'secret',
            'clientToken' => 'client-token-1',
        ]);
        $response = $this->harness->invokeRoute($route, $request);

        $this->assertSame(200, $response['status']);
        $this->assertArrayHasKey('accessToken', $response['json']);
        $this->assertArrayHasKey('clientToken', $response['json']);
        $this->assertArrayHasKey('selectedProfile', $response['json']);
        $this->assertSame('Steve', $response['json']['selectedProfile']['name']);
    }

    public function testInvalidCredentialsRejected(): void
    {
        $this->harness->createAccount('Steve', 'secret');
        $route = $this->harness->getContainer()->get(Authenticate::class);
        $request = $this->harness->makeRequest('POST', '/authenticate', [
            'username' => 'Steve',
            'password' => 'wrong',
            'clientToken' => 'client-token-1',
        ]);
        $response = $this->harness->invokeRoute($route, $request);

        $this->assertSame(403, $response['status']);
        $this->assertSame('ForbiddenOperationException', $response['json']['error']);
    }
}
