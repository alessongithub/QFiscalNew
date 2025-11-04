<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckBalanceAvailability;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendar atualização de uso de storage diariamente às 2h
Schedule::command('storage:update-usage')
    ->dailyAt('02:00')
    ->description('Atualizar uso de storage de todos os tenants');

// Agendar purga de auditorias antigas diariamente às 03:00
Schedule::command('audits:purge-old')
    ->dailyAt('03:00')
    ->description('Remover auditorias acima da retenção configurada');

// Verificar liquidação de saldos (boletos) a cada 6 horas
Schedule::job(new CheckBalanceAvailability())
    ->everySixHours()
    ->description('Verificar liquidação de boletos e disponibilizar saldo para transferência');
