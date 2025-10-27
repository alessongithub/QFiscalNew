<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDashboardLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'container_name',
        'x_position',
        'y_position',
        'width',
        'height',
    ];
}


