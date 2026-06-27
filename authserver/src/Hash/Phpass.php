<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Hash;

use Craftorio\Authserver\Entity\AccountInterface;
use Phpass\Hash;

/**
 * Class Phpass
 * @package Craftorio\Authserver\Hash
 */
class Phpass implements HashInterface
{
    private $hash;

    /**
     * Phpass constructor.
     * @param Hash $hash
     */
    public function __construct(Hash $hash)
    {
        $this->hash = $hash;
    }

    /**
     * @param int $len
     * @param null $chars
     * @return string
     * @throws \Exception
     */
    private function getRandomString($len = 32, $chars = null): string
    {
        if (is_null($chars)) {
            $chars = self::CHARS_LOWERS . self::CHARS_UPPERS . self::CHARS_DIGITS;
        }
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
            $str .= $chars[random_int(0, $lc)];
        }

        return $str;
    }


    /**
     * @param string $password
     * @return string
     * @throws \Exception
     */
    /**
     * Stored format: "{phpass_hash}:{salt}".
     * Salt is prepended to password before phpass hashing (not appended after hash).
     */
    public function hashPassword(string $password): string
    {
        $salt = $this->getRandomString();
        $hash = $this->hash->hashPassword($salt . $password);

        return "{$hash}:{$salt}";
    }

    /**
     * @param AccountInterface $account
     * @param string $password
     * @return bool
     */
    public function checkPassword(AccountInterface $account, string $password): bool
    {
        $parts = explode(':', $account->getPasswordHash());
        $hash = $parts[0] ?? '';
        $salt = $parts[1] ?? '';

        return $this->hash->checkPassword($salt . $password, $hash);
    }
}
