<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'body', 'content', 'image_url', 'link_url', 'active', 'published_at'
    ];

    protected $casts = [
        'active' => 'boolean',
        'published_at' => 'datetime',
    ];
}


