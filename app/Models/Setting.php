<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id','key','value'];

    public static function get(string $key, $default = null)
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        if (!$tenantId) { return $default; }
        $rec = static::where('tenant_id',$tenantId)->where('key',$key)->first();
        return $rec ? $rec->value : $default;
    }

    public static function set(string $key, $value): void
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        if (!$tenantId) { return; }
        static::updateOrCreate(['tenant_id'=>$tenantId,'key'=>$key], ['value'=>$value]);
    }

    // Método para configurações globais do admin (sem tenant_id)
    public static function setGlobal(string $key, $value): void
    {
        static::updateOrCreate(['tenant_id'=>null,'key'=>$key], ['value'=>$value]);
    }

    public static function getGlobal(string $key, $default = null)
    {
        $rec = static::where('tenant_id', null)->where('key', $key)->first();
        return $rec ? $rec->value : $default;
    }
}


