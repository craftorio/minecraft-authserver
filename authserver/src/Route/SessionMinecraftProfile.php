<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Route;

use Craftorio\Authserver\Authenticator\AuthenticatorInterface;
use Craftorio\Authserver\ProfileApiLog;
use stdClass;

/**
 * Interface RouteInterface
 * @package Craftorio\Route
 */
class SessionMinecraftProfile implements RouteInterface
{
    private $authenticator;

    /**
     * SessionMinecraftJoin constructor.
     * @param AuthenticatorInterface|Authenticator $authenticator
     */
    public function __construct(AuthenticatorInterface $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function getPath(): string
    {
        return 'GET /session/minecraft/profile/@profile';
    }

    public function __invoke(...$args)
    {
        $profileId = $args[0] ?? '';
        ProfileApiLog::write('session/minecraft/profile', [
            'profile_id' => $profileId,
            'note' => 'GET profile textures',
        ]);

        $profile = $this->authenticator->getProfile($profileId);

        ProfileApiLog::write('session/minecraft/profile', [
            'profile_id' => $profileId,
            'found' => !empty($profile),
            'name' => $profile['name'] ?? null,
        ]);

        \Flight::json($profile ? $profile : null);
    }
}
