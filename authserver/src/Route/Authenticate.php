<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Route;

use Craftorio\Authserver\Entity\AccountInterface;
use Craftorio\Authserver\Account\Storage\StorageInterface;
use Craftorio\Authserver\Authenticator\AuthenticatorInterface;

/**
 * Interface RouteInterface
 * @package Craftorio\Route
 */
class Authenticate implements RouteInterface
{
    private $storage;
    private $authenticator;

    /**
     * Authenticate constructor.
     * @param StorageInterface $storage
     * @param AuthenticatorInterface $authenticator
     */
    public function __construct(StorageInterface $storage, AuthenticatorInterface $authenticator)
    {
        $this->storage = $storage;
        $this->authenticator = $authenticator;
    }

    public function getPath(): string
    {
        return 'POST /authenticate';
    }

    /**
     * @param ...$args
     * @throws \Exception
     */
    public function __invoke(...$args)
    {
        $payload = \Flight::request()->data;
        $username = $payload['username'] ?? null;
        $password = $payload['password'] ?? null;
        $clientToken = $payload['clientToken']  ?? null;

        if (!$username || !$password || !$clientToken) {
            // Flight sends HTTP 400 via response()->status; JSON body uses 403 per Yggdrasil error shape.
            \Flight::response()->status(400)->send();
            \Flight::json([
                "error" => "InvalidRequestException", // Package ca.uhn.fhir.rest.server.exceptions
                "errorMessage" => "Bad Request."
            ], 403);

            return;
        }

        $account = $this->loadAccount($username);
        if (!$account) {
            \Flight::json([
                "error" => "ForbiddenOperationException", // Package ca.uhn.fhir.rest.server.exceptions
                "errorMessage" => "Invalid credentials. Invalid username or password."
            ], 403);

            return;
        }

        $sessionInfo = $this->authenticator->authenticateByPassword($account, $password, $clientToken);
        if (!$sessionInfo) {
            \Flight::json([
                "error" => "ForbiddenOperationException",
                "errorMessage" => "Invalid credentials. Invalid username or password."
            ], 403);

            return;
        }

        \Flight::json($sessionInfo);
    }

    /**
     * @param string $username
     * @return AccountInterface|null
     */
    /**
     * Login field accepts username or email — Yggdrasil clients may send either in "username".
     */
    private function loadAccount(string $username): ?AccountInterface
    {
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            return $this->storage->findByEmail($username);
        }

        return $this->storage->findByUsername($username);
    }
}
