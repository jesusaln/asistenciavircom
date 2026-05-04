<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use App\Models\Concerns\BelongsToEmpresa;

class KnowledgeBaseArticle extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected static array $columnExistsCache = [];

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'user_id',
        'titulo',
        'slug',
        'contenido',
        'resumen',
        'tags',
        'vistas',
        'util_si',
        'util_no',
        'destacado',
        'publicado',
    ];

    protected $casts = [
        'tags' => 'array',
        'vistas' => 'integer',
        'util_si' => 'integer',
        'util_no' => 'integer',
        'destacado' => 'boolean',
        'publicado' => 'boolean',
    ];

    // Boot - Generar slug automático
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->titulo) . '-' . Str::random(6);
            }
        });
    }

    // Relaciones
    // Relaciones

    public function categoria()
    {
        return $this->belongsTo(TicketCategory::class, 'categoria_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopePublicados($query)
    {
        if (!self::hasColumn('publicado')) {
            return $query;
        }

        return $query->where('publicado', true);
    }

    public function scopeDestacados($query)
    {
        if (!self::hasColumn('destacado')) {
            return $query;
        }

        return $query->where('destacado', true);
    }

    public function scopePopulares($query)
    {
        return $query->orderByDesc('vistas');
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $pattern = "%{$termino}%";
            $q->whereRaw("unaccent(titulo) ILIKE unaccent(?)", [$pattern])
                ->orWhereRaw("unaccent(contenido) ILIKE unaccent(?)", [$pattern]);
        });
    }

    // Métodos
    public function incrementarVistas(): void
    {
        $this->increment('vistas');
    }

    public function marcarUtil(bool $util): void
    {
        if ($util) {
            $this->increment('util_si');
        } else {
            $this->increment('util_no');
        }
    }

    public function getPorcentajeUtilAttribute(): int
    {
        $total = $this->util_si + $this->util_no;
        if ($total === 0)
            return 0;

        return (int) round(($this->util_si / $total) * 100);
    }

    protected static function hasColumn(string $column): bool
    {
        $key = static::class . ':' . $column;

        if (!array_key_exists($key, self::$columnExistsCache)) {
            try {
                self::$columnExistsCache[$key] = Schema::hasColumn((new static())->getTable(), $column);
            } catch (\Throwable $e) {
                self::$columnExistsCache[$key] = false;
            }
        }

        return self::$columnExistsCache[$key];
    }
}
