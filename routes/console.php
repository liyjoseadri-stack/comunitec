<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('cotizaciones:recordar-vencimiento')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('cotizaciones:vencer')->dailyAt('08:10')->withoutOverlapping();
