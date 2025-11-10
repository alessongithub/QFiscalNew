<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GatewayConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider', 'mode',
        'public_key_sandbox', 'access_token_sandbox',
        'public_key_production', 'access_token_production', 'client_id_production', 'client_secret_production',
        'webhook_secret', 'block_login_after_days',
        // Celcoin
        'celcoin_client_id', 'celcoin_client_secret', 'celcoin_webhook_secret',
        'celcoin_webhook_type', 'celcoin_webhook_login', 'celcoin_webhook_pwd',
        'celcoin_galax_id', 'celcoin_galax_hash', 'celcoin_public_token', 'celcoin_api_version',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'provider' => 'mercadopago',
            'mode' => 'sandbox',
            'block_login_after_days' => 3,
        ]);
    }

    public function getActivePublicKeyAttribute(): ?string
    {
        if ($this->mode === 'production') {
            return $this->public_key_production ?: ($this->public_key_sandbox ?: null);
        }
        return $this->public_key_sandbox ?: ($this->public_key_production ?: null);
    }

    public function getActiveAccessTokenAttribute(): ?string
    {
        if ($this->mode === 'production') {
            return $this->access_token_production ?: ($this->access_token_sandbox ?: null);
        }
        return $this->access_token_sandbox ?: ($this->access_token_production ?: null);
    }
}


