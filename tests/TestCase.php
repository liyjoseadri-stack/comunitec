<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $entorno = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: null;
        $conexion = $_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: null;

        if ($entorno !== 'testing' || $conexion !== 'sqlite') {
            throw new \RuntimeException(
                'Las pruebas se detuvieron por seguridad: requieren APP_ENV=testing y DB_CONNECTION=sqlite. '
                .'No se ejecutarán contra la base configurada en .env.'
            );
        }

        return parent::createApplication();
    }
}
