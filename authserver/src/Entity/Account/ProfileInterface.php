<?php

namespace Craftorio\Authserver\Entity\Account;

/**
 * Interface ProfileInterface
 * @package Craftorio\Authserver\Entity\Account
 */
interface ProfileInterface extends \JsonSerializable
{
    /**
     * @return string
     */
    public function getUuid(): string;

    /**
     * @return string
     */
    public function getName(): string;
}