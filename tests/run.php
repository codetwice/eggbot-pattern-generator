<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$homeRequest = new App\Http\Request('GET', '/', [], [], [], []);
$homeResponse = $app->handle($homeRequest);
if ($homeResponse->getStatus() !== 200) {
    fwrite(STDERR, "Homepage did not return HTTP 200\n");
    exit(1);
}

$generatorsRequest = new App\Http\Request('GET', '/generators', [], [], [], []);
$generatorsResponse = $app->handle($generatorsRequest);
if ($generatorsResponse->getStatus() !== 200) {
    fwrite(STDERR, "Generator endpoint did not return HTTP 200\n");
    exit(1);
}

$data = json_decode($generatorsResponse->getContent(), true);
if (! is_array($data) || count($data) === 0) {
    fwrite(STDERR, "Generator endpoint returned invalid data\n");
    exit(1);
}

echo "All application smoke tests passed.\n";
