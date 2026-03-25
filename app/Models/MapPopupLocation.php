<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapPopupLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'land_id',
        'title',
        'sort_order',
    ];
}