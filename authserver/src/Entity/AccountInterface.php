<?php

namespace Craftorio\Authserver\Entity;

use Craftorio\Authserver\Entity\Account\ProfileInterface;

interface AccountInterface extends \JsonSerializable
{
    /**
     * @return string
     */
    public function getId(): ?string;

    /**
     * @return string
     */
    public function getUuid(): ?string;

    /**
     * @return string
     */
    public function getUsername(): ?string;

    /**
     * @return string
     */
    public function getEmail(): ?string;

    /**
     * @return string
     */
    public function getPasswordHash(): ?string;

    /**
     * @return string
     */
    public function getIpAddress(): ?string;

    /**
     * @return ProfileInterface
     */
    public function getSelectedProfile(): ?ProfileInterface;

    /**
     * @return ProfileInterface[]
     */
    public function getProfiles(): array;
}