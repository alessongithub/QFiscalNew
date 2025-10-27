<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Setting;
use App\Models\TenantEmitter;

$tenantId = auth()->id() ? auth()->user()->tenant_id : (int) (getenv('TENANT_ID') ?: 1);
$serial = Setting::get('nfe.certificate_serial');
$emitter = TenantEmitter::where('tenant_id', $tenantId)->first();
$path = $emitter?->certificado_caminho;
$abs1 = $path ? $path : '';
$abs2 = $path ? (function($p){ return storage_path('app/'.ltrim($p,'/')); })($path) : '';
echo "tenant_id={$tenantId}\n";
echo "nfe.certificate_serial=".($serial ?: '(null)')."\n";
echo "emitter.path=".($path ?: '(null)')."\n";
if ($abs1) echo "exists(abs1)=".(file_exists($abs1)?'yes':'no')." => {$abs1}\n";
if ($abs2) echo "exists(abs2)=".(file_exists($abs2)?'yes':'no')." => {$abs2}\n";
echo "has_password=".(!empty($emitter?->certificado_senha)?'yes':'no')."\n";
echo "OK\n";


