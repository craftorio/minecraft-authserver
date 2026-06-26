<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Route;

use Craftorio\Authserver\Authenticator\AuthenticatorInterface;
use Craftorio\Authserver\ProfileApiLog;

/**
 * Mojang 1.20+ POST /minecraft/profile/lookup/bulk/byname
 */
class ProfileLookupBulkByName implements RouteInterface
{
    private $authenticator;

    public function __construct(AuthenticatorInterface $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function getPath(): string
    {
        return 'POST /minecraft/profile/lookup/bulk/byname';
    }

    public function __invoke(...$args)
    {
        $raw = ProfileApiLog::rawBody();
        ProfileApiLog::write('profile/lookup/bulk/byname', [
            'raw_body' => $raw,
            'note' => '1.20+ multiplayer name lookup',
        ]);

        $names = json_decode($raw, true);
        if (!is_array($names)) {
            ProfileApiLog::write('profile/lookup/bulk/byname', ['error' => 'invalid_json']);
            \Flight::json([]);
            return;
        }

        $out = [];
        foreach ($names as $name) {
            if (!is_string($name)) {
                continue;
            }
            $profile = $this->authenticator->getProfileByUsername($name);
            if ($profile) {
                $out[] = [
                    'id' => $profile['id'],
                    'name' => $profile['name'],
                ];
            }
        }

        ProfileApiLog::write('profile/lookup/bulk/byname', [
            'request_names' => $names,
            'response' => $out,
        ]);

        \Flight::json($out);
    }
}
