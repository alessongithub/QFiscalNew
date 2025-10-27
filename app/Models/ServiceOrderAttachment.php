<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'service_order_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function serviceOrder() { return $this->belongsTo(ServiceOrder::class); }
}


