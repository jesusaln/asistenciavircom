<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Proveedor extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected static function booted()
    {
        static::creating(function (Proveedor $proveedor) {
            if (empty($proveedor->codigo)) {
                try {
                    $proveedor->codigo = app(\App\Services\Folio\FolioService::class)->getNextFolio('proveedor');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for proveedor: ' . $e->getMessage());
                }
            }
        });
    }

    protected $table = 'proveedores';

    // Campos que pueden ser asignados masivamente
    protected $fillable = [
        'codigo',
        'empresa_id',
        'nombre_razon_social',
        'tipo_persona',
        'rfc',
        'regimen_fiscal',
        'uso_cfdi',
        'email',
        'telefono',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'codigo_postal',
        'municipio',
        'estado',
        'pais',
        'activo',
    ];

    /**
     * Relación con las compras.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function compras()
    {
        return $this->hasMany(Compra::class); // Relación uno a muchos
    }

    /**
     * Relación con las órdenes de compra.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class);
    }

    /**
     * Relación con los productos.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
