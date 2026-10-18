<?php

use App\Jobs\Vacantes\CerrarVacantesVencidas;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CerrarVacantesVencidas)
    ->dailyAt('00:05')
    ->name('cerrar-vacantes-vencidas')
    ->withoutOverlapping(60)
    ->onOneServer();
