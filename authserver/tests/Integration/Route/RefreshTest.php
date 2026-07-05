<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Tests\Integration\Route;

use Craftorio\Authserver\Route\Authenticate;
use Craftorio\Authserver\Route\Refresh;
use Craftorio\Authserver\Tests\Support\AppTestHarness;
use PHPUnit\Framework\TestCase;

final class RefreshTest extends TestCase
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

    public function testValidRefreshReturnsAccessToken(): void
    {
        $this->harness->createAccount('Steve', 'secret');

        $authenticate = $this->harness->getContainer()->get(Authenticate::class);
        $authRequest = $this->harness->makeRequest('POST', '/authenticate', [
            'username' => 'Steve',
            'password' => 'secret',
            'clientToken' => 'client-token-1',
        ]);
        $authResponse = $this->harness->invokeRoute($authenticate, $authRequest);

        $refresh = $this->harness->getContainer()->get(Refresh::class);
        $refreshRequest = $this->harness->makeRequest('POST', '/refresh', [
            'accessToken' => $authResponse['json']['accessToken'],
            'clientToken' => $authResponse['json']['clientToken'],
        ]);
        $refreshResponse = $this->harness->invokeRoute($refresh, $refreshRequest);

        $this->assertSame(200, $refreshResponse['status']);
        $this->assertArrayHasKey('accessToken', $refreshResponse['json']);
        $this->assertArrayHasKey('selectedProfile', $refreshResponse['json']);
        $this->assertSame('client-token-1', $refreshResponse['json']['clientToken']);
    }
}
