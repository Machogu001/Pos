<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Department;

$rows = Department::with('employee')->get()->map(function($d){
    return [
        'id' => $d->id,
        'department' => $d->department,
        'department_head' => $d->department_head,
        'employee_username' => optional($d->employee)->username
    ];
});

echo json_encode($rows->toArray(), JSON_PRETTY_PRINT);
