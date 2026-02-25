<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'titulo',
        'slug',
        'resumen',
        'contenido',
        'imagen_portada',
        'categoria',
        'status',
        'publicado_at',
        'visitas',
        'meta_titulo',
        'meta_descripcion',
        'newsletter_enviado_at',
    ];

    protected $appends = ['imagen_portada_url', 'tiempo_lectura'];

    protected $casts = [
        'publicado_at' => 'datetime',
        'visitas' => 'integer',
        'newsletter_enviado_at' => 'datetime',
    ];

    /**
     * Obtener URL completa de la imagen de portada
     */
    public function getImagenPortadaUrlAttribute()
    {
        if (!$this->imagen_portada) {
            return null;
        }

        if (str_starts_with($this->imagen_portada, 'http')) {
            return $this->imagen_portada;
        }

        // Local public images (e.g., /images/blog/...)
        if (str_starts_with($this->imagen_portada, '/images/')) {
            return url($this->imagen_portada);
        }

        $normalizedPath = ltrim($this->imagen_portada, '/');
        if (str_starts_with($normalizedPath, 'storage/')) {
            $normalizedPath = substr($normalizedPath, strlen('storage/'));
        }

        if (str_starts_with($normalizedPath, 'blog-covers/')) {
            $filename = basename($normalizedPath);
            return route('serve-blog-cover', ['filename' => $filename]);
        }

        if (Storage::disk('public')->exists($normalizedPath)) {
            return url(Storage::url($normalizedPath));
        }

        return url('/images/placeholder-400x400.svg');
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->titulo) . '-' . Str::random(5);
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('publicado_at', '<=', now());
    }

    public function scopePublicado($query)
    {
        return $this->scopePublished($query);
    }

    public function getTiempoLecturaAttribute()
    {
        $words = str_word_count(strip_tags($this->contenido));
        $minutes = ceil($words / 200);
        return $minutes . ' min';
    }
}
