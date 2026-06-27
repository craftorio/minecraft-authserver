<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Route;

use Craftorio\Authserver\Config;

/**
 * @package Craftorio\Route
 */
class PublicKeys implements RouteInterface
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getPath(): string
    {
        return 'GET /publickeys';
    }

    public function __invoke(...$args)
    {
        $pemFile = $this->config->get('certificatesDir') . DIRECTORY_SEPARATOR . 'yggdrasil_session_public.pem';
        if (!is_readable($pemFile)) {
            throw new \Exception("Can't read pem file");
        }

        $lines = explode("\n", trim(file_get_contents($pemFile)));
        array_shift($lines);
        array_pop($lines);
        $base64Der = implode('', $lines);

        // Mojang 1.20+ exposes the same key in both arrays for profile properties and player certificates.
        $key = ['publicKey' => $base64Der];

        \Flight::json([
            'profilePropertyKeys'    => [$key],
            'playerCertificateKeys'  => [$key],
        ]);
    }
}
