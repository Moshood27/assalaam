<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-wellness-check')->daily();
Schedule::command('app:apply-monthly-fines')->monthlyOn(5, '00:00');
Schedule::command('telescope:prune')->daily();
Schedule::command('model:prune')->daily();
Schedule::command('activitylog:clean')->daily();
Schedule::command('queue:prune-failed --hours=48')->daily();
Schedule::command('queue:prune-batches --hours=48')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('02:00');
