<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\User::where('role', 'admin')->first();
if (! $user) {
    echo "NO_ADMIN_USER\n";
    exit(0);
}

Illuminate\Support\Facades\Auth::login($user);
$request = Illuminate\Http\Request::create('/superadmin/admin-panel', 'GET');
$request->setUserResolver(function () use ($user) {
    return $user;
});
$app->instance('request', $request);

echo 'USER=' . $user->id . PHP_EOL;

$start = microtime(true);
$controller = $app->make(App\Http\Controllers\AdminController::class);
$response = $controller->index();
$controllerElapsed = microtime(true) - $start;

echo 'CLASS=' . get_class($response) . PHP_EOL;
echo 'CONTROLLER_TIME=' . $controllerElapsed . PHP_EOL;

$renderStart = microtime(true);
$html = $response->render();
$renderElapsed = microtime(true) - $renderStart;

echo 'RENDER_TIME=' . $renderElapsed . PHP_EOL;
echo 'HTML_BYTES=' . strlen($html) . PHP_EOL;
