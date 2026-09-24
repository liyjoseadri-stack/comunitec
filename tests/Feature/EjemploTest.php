<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EjemploTest extends TestCase
{
    /**
     * Prueba básica de funcionamiento.
     */
    public function test_el_inicio_redirige_al_panel(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/panel');
    }
}
