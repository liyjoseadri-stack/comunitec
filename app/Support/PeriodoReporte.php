<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class PeriodoReporte
{
    public function __construct(
        public readonly CarbonImmutable $inicio,
        public readonly CarbonImmutable $finExclusivo
    ) {}

    public static function desdeMes(?string $mes): self
    {
        $inicio = $mes === null
            ? CarbonImmutable::now()->startOfMonth()
            : CarbonImmutable::createFromFormat('!Y-m', $mes);

        return new self($inicio, $inicio->addMonth());
    }

    public function mes(): string
    {
        return $this->inicio->format('Y-m');
    }

    public function etiqueta(): string
    {
        return ucfirst($this->inicio->locale('es')->translatedFormat('F Y'));
    }

    public function anterior(): string
    {
        return $this->inicio->subMonth()->format('Y-m');
    }

    public function siguiente(): string
    {
        return $this->inicio->addMonth()->format('Y-m');
    }
}
