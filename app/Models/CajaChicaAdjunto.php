<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CajaChicaAdjunto extends Model
{
    use HasFactory;

    protected $table = 'caja_chica_adjuntos';

    protected $fillable = [
        'caja_chica_id',
        'path',
    ];

    protected $appends = ['url'];

    public function cajaChica()
    {
        return $this->belongsTo(CajaChica::class);
    }

    public function getUrlAttribute()
    {
        return $this->path ? Storage::url($this->path) : null;
    }
}
