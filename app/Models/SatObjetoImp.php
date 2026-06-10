<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatObjetoImp extends Model
{
    protected $table = 'sat_objetos_imp';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['clave', 'descripcion', 'activo'];

    public static function getOptions(): array
    {
        $options = self::where('activo', true)
            ->select('clave', 'descripcion as nombre')
            ->get()
            ->toArray();

        if (!empty($options)) {
            return $options;
        }

        return [
            ['clave' => '01', 'nombre' => 'No objeto de impuesto'],
            ['clave' => '02', 'nombre' => 'Sí objeto de impuesto'],
            ['clave' => '03', 'nombre' => 'Sí objeto de impuesto y no obligado al desglose'],
        ];
    }
}
