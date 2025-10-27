<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Setting;

class NFSeService
{
    private string $endpoint;
    private int $timeout;

    public function __construct()
    {
        $this->endpoint = Setting::get('services.delphi.url', config('services.delphi.url', 'http://localhost:18080'));
        $this->timeout = (int) Setting::get('services.delphi.timeout', (int) config('services.delphi.timeout', 60));
    }

    public function emitir(array $payload): array
    {
        $http = Http::timeout($this->timeout);
        $token = Setting::get('services.delphi.token');
        if (!empty($token)) { $http = $http->withToken($token); }
        $res = $http->post(rtrim($this->endpoint,'/').'/api/emitir-nfse', $payload);
        if ($res->successful()) { return ['success'=>true, 'data'=>$res->json()]; }
        return ['success'=>false, 'error'=>$res->body(), 'status'=>$res->status()];
    }
}


