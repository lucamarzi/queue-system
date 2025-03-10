<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'counter'
    ];
    
    protected $casts = [
        'date' => 'date',
    ];
}
