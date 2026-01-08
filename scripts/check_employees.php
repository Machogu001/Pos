<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;

$rows = Employee::whereNull('deleted_at')->get(['id','username','firstname','lastname'])->toArray();

echo json_encode($rows, JSON_PRETTY_PRINT);
