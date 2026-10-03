<?php

require dirname(__DIR__).'/app/Services/RuntimePermissions.php';

try {
    $webUser = $argv[1] ?? (getenv('POS_WEB_USER') ?: null);
    (new \App\Services\RuntimePermissions())->repair(dirname(__DIR__), $webUser);
    fwrite(STDOUT, "Runtime permissions repaired and file locking verified.\n");
} catch (\RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}
