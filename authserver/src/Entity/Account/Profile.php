<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Entity\Account;

use Ramsey\Uuid\Uuid;

/**
 * Class Profile
 * @package Craftorio\Authserver\Entity\Account
 */
class Profile implements ProfileInterface
{
    private $id;
    private $uuid;
    private $name;

    /**
     * Profile constructor.
     * @param array $rawData
     */
    public function __construct(array $rawData)
    {
        $this->uuid = (string) $rawData['uuid'] ?? Uuid::uuid4();
        $this->name = (string) $rawData['name'] ?? 'Unnamed';
        // Mojang profile id: md5 of profile uuid (hex, no dashes) — see ProfileId::normalize().
        $this->id = md5($this->uuid);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
        ];
    }
}
