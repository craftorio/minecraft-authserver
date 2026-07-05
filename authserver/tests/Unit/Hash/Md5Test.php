<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Tests\Unit\Hash;

use Craftorio\Authserver\Entity\Account;
use Craftorio\Authserver\Hash\Md5;
use PHPUnit\Framework\TestCase;

final class Md5Test extends TestCase
{
    public function testHashAndVerifyPassword(): void
    {
        $hash = new Md5();
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
