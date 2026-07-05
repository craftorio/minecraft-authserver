<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Tests\Integration\Route;

use Craftorio\Authserver\Route\ProfileLookupBulkByName;
use Craftorio\Authserver\Tests\Support\AppTestHarness;
use PHPUnit\Framework\TestCase;

final class ProfileLookupBulkByNameTest extends TestCase
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

    public function testBulkLookupReturnsKnownProfile(): void
    {
        $account = $this->harness->createAccount('Steve', 'secret');
        $route = $this->harness->getContainer()->get(ProfileLookupBulkByName::class);
        $request = $this->harness->makeRequest(
            'POST',
            '/minecraft/profile/lookup/bulk/byname',
            [],
            [],
            '["Steve","Unknown"]',
            'application/json'
        );

        $response = $this->harness->invokeRoute($route, $request);

        $this->assertSame(200, $response['status']);
        $this->assertCount(1, $response['json']);
        $this->assertSame('Steve', $response['json'][0]['name']);
        $this->assertSame($account->getSelectedProfile()->getId(), $response['json'][0]['id']);
    }
}
