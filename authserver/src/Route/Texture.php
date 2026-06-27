<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Route;

use Craftorio\Authserver\Skin;

/**
 * Interface RouteInterface
 * @package Craftorio\Route
 */
class Texture implements RouteInterface
{
    private $skin;

    public function __construct(Skin $skin)
    {
        $this->skin = $skin;
    }

    public function getPath(): string
    {
        return 'GET /texture/@hash';
    }

    public function __invoke(...$args)
    {
        // hash maps to a SleekDB skin index row; path points at local PNG (not Mojang CDN).
        $skin = $this->skin->getStore()->findOneBy(['hash', '=', $args[0]]) ?? [];

        if (!empty($skin['path']) && is_readable($skin['path'])) {    
            header ('Content-Type: image/png');
            header ("Content-length: " . filesize($skin['path']));

            echo file_get_contents($skin['path']);
        } else {
            \Flight::response()->status(404)->send();
        }
    }
}
