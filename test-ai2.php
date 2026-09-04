<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Ai\Agents\ArchiveClassifier;

$agent = new ArchiveClassifier();
try {
    $response = $agent->prompt('Surat cuti sakit');
    echo "Success! \n";
    print_r($response->result());
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
