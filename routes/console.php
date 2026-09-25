<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('cotizaciones:vencer')->daily();
