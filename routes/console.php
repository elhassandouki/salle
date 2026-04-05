<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:sync-zk-access')
    ->cron('*/' . max(1, min(59, (int) config('zkteco.sync.interval', 60))) . ' * * * *')
    ->withoutOverlapping();
