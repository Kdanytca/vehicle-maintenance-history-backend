<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\GenerarAlertasGarantia;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('garantias:generar-alertas', function () {
    app(GenerarAlertasGarantia::class)->ejecutar();
    $this->info('Alertas de garantía actualizadas.');
})->purpose('Generar avisos de garantía próximos a vencer y vencidos');

Schedule::command('garantias:generar-alertas')->daily()->withoutOverlapping();
