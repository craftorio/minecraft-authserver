<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Route;

use Craftorio\Authserver\Authenticator\Authenticator;
use Craftorio\Authserver\Authenticator\AuthenticatorInterface;
use Craftorio\Authserver\Authenticator\Exception\UnauthorizedException;
use Craftorio\Authserver\ProfileApiLog;

/**
 * Interface RouteInterface
 * @package Craftorio\Route
 */
class SessionMinecraftHasJoined implements RouteInterface
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

    /**
     * @return string
     */
    public function getPath(): string
    {
        return 'GET /session/minecraft/hasJoined';
    }

    /**
     * Check is the server accepted by user
     *
     * @param ...$args
     * @throws \Exception
     */
    public function __invoke(...$args)
    {
        $serverId = \Flight::request()->query['serverId'] ?? null;
        $username = \Flight::request()->query['username'] ?? null;

        ProfileApiLog::write('session/minecraft/hasJoined', [
            'serverId' => $serverId,
            'username' => $username,
            'note' => 'hasJoined request',
        ]);

        try {
            if (!$serverId || !$username) {
                ProfileApiLog::write('session/minecraft/hasJoined', [
                    'serverId' => $serverId,
                    'username' => $username,
                    'status' => 401,
                    'reason' => 'missing_serverId_or_username',
                ]);
                \Flight::response()->status(401)->send();

                return;
            }

            $sessionInfo = $this->authenticator->hasJoinedServer($serverId, $username);

            ProfileApiLog::write('session/minecraft/hasJoined', [
                'serverId' => $serverId,
                'username' => $username,
                'status' => 200,
                'profile_id' => $sessionInfo['id'] ?? null,
                'profile_name' => $sessionInfo['name'] ?? null,
            ]);

            \Flight::json($sessionInfo);
        } catch (UnauthorizedException $e) {
            ProfileApiLog::write('session/minecraft/hasJoined', [
                'serverId' => $serverId,
                'username' => $username,
                'status' => 401,
                'reason' => 'unauthorized',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            \Flight::response()->status(401)->send();

            return;
        } catch (\Throwable $e) {
            ProfileApiLog::write('session/minecraft/hasJoined', [
                'serverId' => $serverId,
                'username' => $username,
                'status' => 500,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            \Flight::response()->status(500)->send();

            return;
        }
    }
}
