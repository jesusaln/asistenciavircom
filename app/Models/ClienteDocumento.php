<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClienteDocumento extends Model
{
    use HasFactory;

    protected $table = 'cliente_documentos';

    protected $fillable = [
        'cliente_id',
        'tipo',
        'nombre_archivo',
        'ruta',
        'extension',
        'tamano',
        'mime_type',
    ];

    protected $appends = ['url'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function getUrlAttribute()
    {
        return Storage::disk('public')->url($this->ruta);
    }
}
