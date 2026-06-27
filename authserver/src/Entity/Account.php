<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Entity;

use Craftorio\Authserver\Entity\Account\Profile;
use Craftorio\Authserver\Entity\Account\ProfileInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class Account
 * @package Craftorio\Authserver
 */
class Account implements AccountInterface
{
    private $id;
    private $uuid;
    private $username;
    private $email;
    private $passwordHash;
    private $ipAddress;
    private $selectedProfile;
    private $externalId;

    /**
     * Account constructor.
     * @param array $rawData
     */
    public function __construct(array $rawData = [])
    {
        $this->id = ((string) ($rawData['_id'] ?? $rawData['id'] ?? null)) ?? null;
        // Stable account uuid: derive from internal id when present, else random v4.
        $this->uuid = (string) ($rawData['uuid'] ?? ($this->id ? Uuid::fromString(md5($this->id)) : Uuid::uuid4()));
        $this->username = $rawData['username'] ?? null;
        $this->email = $rawData['email'] ?? null;
        $this->passwordHash = $rawData['password_hash'] ?? null;
        $this->ipAddress = $rawData['ip_address'] ?? null;
        $this->externalId = $rawData['external_id'] ?? null;

        if (!$this->email || !$this->username || !$this->passwordHash) {
            throw new \RuntimeException('Following fields are required: email, username, password_hash');
        }

        // Single-profile model: profile uuid = md5(account uuid), name = username (Mojang-style).
        // Stored profile data from DB is not used — see commented selected_profile branch below.
        // if (!empty($rawData['selected_profile'])) {
        //     $this->selectedProfile = !empty($rawData['selected_profile']) ? new Profile($rawData['selected_profile']) : null;
        // } else {
            $this->selectedProfile = new Profile([
                'uuid' => (string) Uuid::fromString(md5($this->uuid)),
                'name' => $this->username
            ]);
        // }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            print_r($rawData);
            throw new \RuntimeException('Invalid email address: "' . $this->email . '"');
        }
    }

    /**
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    /**
     * @return string
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * @return ProfileInterface
     */
    public function getSelectedProfile(): ?ProfileInterface
    {
        return $this->selectedProfile;
    }

    /**
     * @return ProfileInterface[]
     */
    /**
     * Yggdrasil returns a one-element availableProfiles array for this authserver.
     */
    public function getProfiles(): array
    {
        return [$this->getSelectedProfile()];
    }

    /**
     * @param string $id
     * @return mixed|void
     */
    public function setExternalId(string $id)
    {
        $this->externalId = $id;
    }

    /**
     * @return string|null
     */
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'uuid' => $this->getUuid(),
            'username' => $this->getUsername(),
            'email' => $this->getEmail(),
            'password_hash' => $this->getPasswordHash(),
            'ip_address' => $this->getIpAddress(),
            'external_id' => $this->getExternalId(),
            'selected_profile' => $this->getSelectedProfile(),
        ];
    }
}
