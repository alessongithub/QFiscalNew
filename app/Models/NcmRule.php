<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NcmRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'ncm',
        'requires_gtin',
        'note',
    ];

    protected $casts = [
        'requires_gtin' => 'boolean',
    ];
}



