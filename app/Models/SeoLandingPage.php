<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;

class SeoLandingPage extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'slug',
        'titulo_h1',
        'meta_description',
        'hero_image_url',
        'hero_title',
        'hero_description',
        'service_category',
        'location',
        'features',
        'content_blocks',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'content_blocks' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope activa
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
