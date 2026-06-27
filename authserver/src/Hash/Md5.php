<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Hash;

use Craftorio\Authserver\Entity\AccountInterface;

/**
 * Legacy MD5 passwords — no salt. Enable via account.hash_algorithm = md5 for old DB compatibility only.
 *
 * @package Craftorio\Authserver\Hash
 */
class Md5 implements HashInterface
{
    /**
     * @param string $password
     * @return string
     * @throws \Exception
     */
    public function hashPassword(string $password): string
    {
        return md5($password);
    }

    /**
     * @param AccountInterface $account
     * @param string $password
     * @return bool
     */
    public function checkPassword(AccountInterface $account, string $password): bool
    {
        return md5($password) === $account->getPasswordHash();
    }
}
