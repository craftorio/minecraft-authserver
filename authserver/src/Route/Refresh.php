<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Route;

use Craftorio\Authserver\Entity\AccountInterface;
use Craftorio\Authserver\Account\Storage\StorageInterface;
use Craftorio\Authserver\Authenticator\AuthenticatorInterface;
use Craftorio\Authserver\Session;

/**
 * Interface RouteInterface
 * @package Craftorio\Route
 */
class Refresh implements RouteInterface
{
    private $storage;
    private $authenticator;
    private $session;

    /**
     * Refresh constructor.
     *
     * @param StorageInterface $storage
     * @param AuthenticatorInterface $authenticator
     * @param Session $session
     */
    public function __construct(StorageInterface $storage, AuthenticatorInterface $authenticator, Session $session)
    {
        $this->storage = $storage;
        $this->authenticator = $authenticator;
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return 'POST /refresh';
    }

    /**
     * @param ...$args
     * @throws \Exception
     */
    public function __invoke(...$args)
    {
        $payload = \Flight::request()->data;
        $accessToken = $payload['accessToken'] ?? null;
        $clientToken = $payload['clientToken'] ?? null;

        if (!$accessToken || !$clientToken) {
            \Flight::response()->status(400)->send();
            \Flight::json([
                "error" => "InvalidRequestException", // Package ca.uhn.fhir.rest.server.exceptions
                "errorMessage" => "Bad Request."
            ], 403);

            return;
        }

        // Both tokens must match the same session row — prevents refresh with a stolen accessToken alone.
        $session = $this->session->getSessionStore()->findOneBy([
            ['accessToken', '=', $accessToken],
            'AND',
            ['clientToken', '=', $clientToken],
        ]);

        if (!$session || !$session['accountId']) {
            \Flight::json([
                "error" => "ForbiddenOperationException", // Package ca.uhn.fhir.rest.server.exceptions
                "errorMessage" => "Invalid credentials. Invalid username or password.",
                "error_code" => "SESSION_NOT_FOUND",
            ], 403);

            return;
        }

        $account = $this->storage->findById($session['accountId']);
        if (!$account) {
            \Flight::json([
                "error" => "ForbiddenOperationException", // Package ca.uhn.fhir.rest.server.exceptions
                "errorMessage" => "Invalid credentials. Invalid username or password.",
                "error_code" => "ACCOUNT_NOT_FOUND",
            ], 403);

            return;
        }

        $sessionInfo = $this->authenticator->refreshSession($account, $clientToken);
        if (!$sessionInfo) {
            \Flight::json([
                "error" => "ForbiddenOperationException",
                "errorMessage" => "Invalid credentials. Invalid username or password.",
                "error_code" => "SESSION_INFO_NOT_FOUND",
            ], 403);

            return;
        }

        \Flight::json($sessionInfo);
    }
}
