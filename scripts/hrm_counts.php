<?php
$autoload = __DIR__ . '/../vendor/autoload.php';
if (! file_exists($autoload)) {
	echo "ERROR: vendor/autoload.php not found\n";
	exit(1);
}
require $autoload;
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "companies:" . \App\Models\Company::count() . "\n";
echo "employees:" . \App\Models\Employee::count() . "\n";
