<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboundInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','supplier_id','access_key','number','series','issue_date',
        'total_products','total_invoice','raw_summary',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'total_products' => 'decimal:2',
        'total_invoice' => 'decimal:2',
        'raw_summary' => 'array',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function items() { return $this->hasMany(InboundInvoiceItem::class); }
}


