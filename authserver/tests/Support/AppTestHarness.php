<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Tests\Support;

use Craftorio\Authserver\Account\Storage\StorageInterface;
use Craftorio\Authserver\Authenticator\AuthenticatorInterface;
use Craftorio\Authserver\Config;
use Craftorio\Authserver\Entity\Account;
use Craftorio\Authserver\Entity\AccountInterface;
use Craftorio\Authserver\Hash;
use Craftorio\Authserver\Authenticator;
use Craftorio\Authserver\Account\Storage;
use DI\Container;
use flight\net\Request;
use flight\net\Response;
use flight\util\Collection;
use function DI\autowire;

/**
 * Builds an isolated DI container and Flight request/response context for route tests.
 */
final class AppTestHarness
{
    /** @var string */
    private $baseDir;

    /** @var Container */
    private $container;

    public function __construct()
    {
        $this->baseDir = sys_get_temp_dir() . '/authserver-test-' . uniqid('', true);
        $varDir = $this->baseDir . '/var';
        mkdir($varDir . '/storage', 0755, true);
        mkdir($varDir . '/certificates', 0755, true);
        mkdir($varDir . '/skins', 0755, true);

        $this->generateTestCertificates($varDir . '/certificates');
        $this->container = $this->buildContainer();
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    /**
     * @return array{status: int, body: string, json: mixed}
     */
    public function invokeRoute(object $route, Request $request): array
    {
        \Flight::init();
        $this->injectFlightRequest($request, new Response());

        ($route)();

        return [
            'status' => \Flight::response()->status(),
            'body' => \Flight::response()->getBody(),
            'json' => \Flight::response()->getBody() === '' ? null : json_decode(\Flight::response()->getBody(), true),
        ];
    }

    private function injectFlightRequest(Request $request, Response $response): void
    {
        $engine = \Flight::app();
        $loaderProperty = new \ReflectionProperty($engine, 'loader');
        $loaderProperty->setAccessible(true);
        $loader = $loaderProperty->getValue($engine);
        $instancesProperty = new \ReflectionProperty($loader, 'instances');
        $instancesProperty->setAccessible(true);
        $instances = $instancesProperty->getValue($loader);
        $instances['request'] = $request;
        $instances['response'] = $response;
        $instancesProperty->setValue($loader, $instances);
    }

    public function makeRequest(
        string $method,
        string $url,
        array $data = [],
        array $query = [],
        string $body = '',
        string $contentType = 'application/x-www-form-urlencoded'
    ): Request {
        if ($query !== []) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }

        return new Request([
            'url' => $url,
            'base' => '',
            'method' => $method,
            'referrer' => '',
            'ip' => '127.0.0.1',
            'ajax' => false,
            'scheme' => 'http',
            'user_agent' => 'PHPUnit',
            'type' => $contentType,
            'length' => strlen($body),
            'query' => new Collection($query),
            'data' => new Collection($data),
            'cookies' => new Collection([]),
            'files' => new Collection([]),
            'secure' => false,
            'accept' => '*/*',
            'proxy_ip' => '',
            'host' => 'localhost',
            'servername' => 'localhost',
            'body' => $body,
        ]);
    }

    public function createAccount(string $username, string $password): AccountInterface
    {
        $storage = $this->container->get(StorageInterface::class);
        $authenticator = $this->container->get(AuthenticatorInterface::class);

        $storage->insert(new Account([
            'username' => $username,
            'email' => $username . '@example.com',
            'password_hash' => $authenticator->hashPassword($password),
        ]));

        $account = $storage->findByUsername($username);
        if (!$account) {
            throw new \RuntimeException('Failed to create test account');
        }

        return $account;
    }

    public function destroy(): void
    {
        $this->removeDirectory($this->baseDir);
    }

    private function buildContainer(): Container
    {
        $container = new Container();
        $container->set(Config::class, new Config([
            'baseDir' => $this->baseDir,
            'certificatesDir' => $this->baseDir . '/var/certificates',
            'skinDir' => $this->baseDir . '/var/skins',
            'account' => [
                'storage' => 'sleekdb',
                'hash_algorithm' => 'default',
                'sleekdb' => [
                    'data_dir' => 'var/storage',
                    'cache_lifetime' => 900,
                ],
            ],
            'sleekDb' => [
                'dataDir' => 'var/storage',
            ],
        ]));
        $container->set(Hash\HashInterface::class, autowire(Hash\Phpass::class));
        $container->set(Authenticator\AuthenticatorInterface::class, autowire(Authenticator\Authenticator::class));
        $container->set(Storage\StorageInterface::class, autowire(Storage\SleekDb::class));

        return $container;
    }

    private function generateTestCertificates(string $dir): void
    {
        $key = openssl_pkey_new([
            'digest_alg' => 'sha1',
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key === false) {
            throw new \RuntimeException('Failed to generate test certificate');
        }

        if (!openssl_pkey_export($key, $private)) {
            throw new \RuntimeException('Failed to export test private key');
        }

        file_put_contents($dir . '/yggdrasil_session_private.pem', $private);

        $details = openssl_pkey_get_details($key);
        file_put_contents($dir . '/yggdrasil_session_public.pem', $details['key']);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
