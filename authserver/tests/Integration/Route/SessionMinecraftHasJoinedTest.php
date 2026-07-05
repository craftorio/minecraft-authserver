<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Tests\Integration\Route;

use Craftorio\Authserver\Route\Authenticate;
use Craftorio\Authserver\Route\SessionMinecraftHasJoined;
use Craftorio\Authserver\Route\SessionMinecraftJoin;
use Craftorio\Authserver\Tests\Support\AppTestHarness;
use PHPUnit\Framework\TestCase;

final class SessionMinecraftHasJoinedTest extends TestCase
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

    public function testHasJoinedAfterJoinReturnsProfile(): void
    {
        $this->harness->createAccount('Steve', 'secret');

        $authenticate = $this->harness->getContainer()->get(Authenticate::class);
        $authResponse = $this->harness->invokeRoute(
            $authenticate,
            $this->harness->makeRequest('POST', '/authenticate', [
                'username' => 'Steve',
                'password' => 'secret',
                'clientToken' => 'client-token-1',
            ])
        );

        $join = $this->harness->getContainer()->get(SessionMinecraftJoin::class);
        $this->harness->invokeRoute(
            $join,
            $this->harness->makeRequest('POST', '/session/minecraft/join', [
                'accessToken' => $authResponse['json']['accessToken'],
                'selectedProfile' => $authResponse['json']['selectedProfile']['id'],
                'serverId' => 'abc123',
            ])
        );

        $hasJoined = $this->harness->getContainer()->get(SessionMinecraftHasJoined::class);
        $response = $this->harness->invokeRoute(
            $hasJoined,
            $this->harness->makeRequest(
                'GET',
                '/session/minecraft/hasJoined',
                [],
                ['username' => 'Steve', 'serverId' => 'abc123']
            )
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame('Steve', $response['json']['name']);
        $this->assertArrayHasKey('id', $response['json']);
        $this->assertArrayHasKey('properties', $response['json']);
    }
}
