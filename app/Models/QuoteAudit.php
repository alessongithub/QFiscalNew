<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'user_id',
        'action',
        'changes',
        'notes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}