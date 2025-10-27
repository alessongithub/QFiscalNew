<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;

Setting::setGlobal('services.delphi.auth', 'query');

$auth = Setting::getGlobal('services.delphi.auth');
$token = Setting::getGlobal('services.delphi.token');
$url = Setting::getGlobal('services.delphi.url');
echo "services.delphi.url={$url}\n";
echo "services.delphi.auth={$auth}\n";
echo "services.delphi.token_present=".(empty($token)?'no':'yes')."\n";
echo "OK\n";


