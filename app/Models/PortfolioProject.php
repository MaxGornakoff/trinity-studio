<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortfolioProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'thumbnail_url',
        'desktop_mockup_image',
        'mobile_mockup_image',
        'project_url',
        'order_column',
        'is_featured',
        'is_published',
        'show_in_slider',
        'region',
        'published_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'show_in_slider' => 'boolean',
        'published_at' => 'datetime',
    ];
}
