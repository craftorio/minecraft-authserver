<?php

declare(strict_types=1);

namespace Craftorio\Authserver;

use Craftorio\Authserver\Hash;
use Craftorio\Authserver\Route\RouteInterface;

/**
 * Class Authserver
 * @package Craftorio\Authserver
 */
class Authserver
{
    /**
     * @var string[]
     */
    private $routes = [
        Route\Home::class,
        Route\PublicKeys::class,
        Route\Texture::class,
        Route\Authenticate::class,
        Route\Refresh::class,
        Route\SessionMinecraftJoin::class,
        Route\SessionMinecraftHasJoined::class,
        Route\SessionMinecraftProfile::class,
    ];

    /** @var \DI\Container */
    private $container;

    /** @var self */
    private static $instance;

    /**
     * Authserver constructor.
     */
    private function __construct()
    {
        $this->configureDI();
    }

    /**
     * Configure Dependency Injection Container
     */
    protected function configureDI(): void
    {
        $this->container = new \DI\Container();
        $this->container->set(
            Config::class,
            \DI\factory(static function () {
                return new Config([]);
            })
        );

        $this->container->set(
            Hash\HashInterface::class,
            \DI\autowire($this->resolveHashAlgorithm())
        );

        $this->container->set(
            Authenticator\AuthenticatorInterface::class,
            \DI\autowire(Authenticator\Authenticator::class)
        );

        $this->container->set(
            Account\Storage\StorageInterface::class,
            \DI\autowire($this->resolveAccountStorage())
        );
    }

    /**
     * @return string
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    private function resolveAccountStorage(): string
    {
        switch ($this->container->get(Config::class)->get('account.storage')) {
            case 'mysql':
                return Account\Storage\Mysql::class;
            default:
                return Account\Storage\SleekDb::class;
        }
    }

    /**
     * @return string
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    private function resolveHashAlgorithm(): string
    {
        switch ($this->container->get(Config::class)->get('account.hash_algorithm')) {
            case 'md5':
                return Hash\Md5::class;
            default:
                return Hash\Phpass::class;
        }
    }

    /**
     * @return \DI\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    private function configureRouter(): void
    {
        foreach ($this->routes as $routeClass) {
            /** @var RouteInterface $route */
            $route = $this->container->get($routeClass);

            \Flight::route($route->getPath(), $route);
        }
    }

    /**
     * Bootstrap application
     * @return Authserver
     */
    public static function load()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }


    /**
     * Web Application Entrypoint
     */
    public static function start(): void
    {
        self::load()
            ->configureRouter();

        \Flight::start();
    }
}
