<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeliCategoryMapping extends Model
{
    protected $table = 'meli_category_mappings';

    protected $fillable = [
        'cva_grupo',
        'meli_category_id',
        'attributes_template',
    ];

    protected $casts = [
        'attributes_template' => 'array',
    ];
}
