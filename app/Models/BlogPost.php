<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;
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

        return url(\Illuminate\Support\Facades\Storage::url($this->imagen_portada));
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
