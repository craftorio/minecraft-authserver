<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Tests\Unit;

use Craftorio\Authserver\ProfileId;
use PHPUnit\Framework\TestCase;

final class ProfileIdTest extends TestCase
{
    public function testOfflineUsernameIsDeterministic(): void
    {
        $first = ProfileId::offlineUsername('Steve');
        $second = ProfileId::offlineUsername('Steve');

        $this->assertSame($first, $second);
        $this->assertSame(32, strlen(ProfileId::normalize($first)));
    }

    public function testNormalizeStripsDashes(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->assertSame('550e8400e29b41d4a716446655440000', ProfileId::normalize($uuid));
    }
}
