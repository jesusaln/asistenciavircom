<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatDescargaDetalle extends Model
{
    use HasFactory;

    protected $table = 'sat_descarga_detalles';

    protected $fillable = [
        'sat_descarga_masiva_id',
        'uuid',
        'direccion',
        'xml_data',
        'xml_content',
        'importado',
        'fecha_emision',
        'rfc_emisor',
        'nombre_emisor',
        'rfc_receptor',
        'nombre_receptor',
        'tipo_comprobante',
        'total',
    ];

    protected $casts = [
        'xml_data' => 'array',
        'importado' => 'boolean',
        'fecha_emision' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function descargaMasiva(): BelongsTo
    {
        return $this->belongsTo(SatDescargaMasiva::class, 'sat_descarga_masiva_id');
    }
}
