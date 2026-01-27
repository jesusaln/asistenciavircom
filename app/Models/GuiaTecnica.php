<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuiaTecnica extends Model
{
    protected $fillable = [
        'nombre',
        'route_name',
        'descripcion',
        'checklist_default',
    ];

    protected $casts = [
        'checklist_default' => 'array',
    ];
}
