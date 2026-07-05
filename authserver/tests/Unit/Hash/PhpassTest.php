<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Tests\Unit\Hash;

use Craftorio\Authserver\Entity\Account;
use Craftorio\Authserver\Hash\Phpass;
use Phpass\Hash;
use PHPUnit\Framework\TestCase;

final class PhpassTest extends TestCase
{
    public function testHashAndVerifyPassword(): void
    {
        $hash = new Phpass(new Hash());
        $password = 'secret';
        $stored = $hash->hashPassword($password);

        $account = new Account([
            'username' => 'Steve',
            'email' => 'steve@example.com',
            'password_hash' => $stored,
        ]);

        $this->assertTrue($hash->checkPassword($account, $password));
        $this->assertFalse($hash->checkPassword($account, 'wrong'));
    }
}
