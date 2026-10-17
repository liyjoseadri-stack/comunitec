<?php

use Illuminate\Support\Facades\Schedule;

$zonaHoraria = config('app.schedule_timezone');

Schedule::command('cotizaciones:recordar-vencimiento')
    ->dailyAt('08:00')
    ->timezone($zonaHoraria)
    ->withoutOverlapping();
Schedule::command('cotizaciones:vencer')
    ->dailyAt('08:10')
    ->timezone($zonaHoraria)
    ->withoutOverlapping();
