<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'features',
        'active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'active' => 'boolean'
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}