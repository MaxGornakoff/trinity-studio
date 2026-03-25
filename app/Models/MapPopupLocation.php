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
        'hover_image',
        'sort_order',
    ];
}