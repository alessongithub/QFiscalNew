<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboundInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbound_invoice_id','product_code','product_name','ean','ncm','cfop','unit','quantity','unit_price','total_price',
        'linked_product_id','linked_movement_id','linked_at','link_locked'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'total_price' => 'decimal:2',
        'linked_at' => 'datetime',
        'link_locked' => 'boolean',
    ];

    public function invoice() { return $this->belongsTo(InboundInvoice::class, 'inbound_invoice_id'); }
}


