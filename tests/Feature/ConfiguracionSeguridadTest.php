<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ConfiguracionSeguridadTest extends TestCase
{
    public function test_la_sesion_conserva_las_protecciones_estructurales_de_su_cookie(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertNotNull(config('session.same_site'));
    }

    public function test_las_respuestas_incluyen_encabezados_basicos_de_seguridad(): void
    {
        $this->get('/iniciar-sesion')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader(
                'Content-Security-Policy',
                "base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'"
            );
    }

    public function test_el_scheduler_usa_la_zona_horaria_operativa_de_mexico(): void
    {
        $this->assertSame('America/Mexico_City', config('app.schedule_timezone'));

        $eventos = collect(app(Schedule::class)->events())
            ->filter(fn ($evento): bool => str_contains($evento->command ?? '', 'cotizaciones:'));

        $this->assertCount(2, $eventos);
        $eventos->each(fn ($evento) => $this->assertSame('America/Mexico_City', $evento->timezone));
    }
}
