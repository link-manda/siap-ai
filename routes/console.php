<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Schedule::call(function () {
    $files = Storage::disk('local')->files('temp');
    foreach ($files as $file) {
        if (now()->subHours(2)->timestamp > Storage::disk('local')->lastModified($file)) {
            Storage::disk('local')->delete($file);
        }
    }
})->hourly();
