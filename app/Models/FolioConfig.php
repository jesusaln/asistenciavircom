<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BelongsToEmpresa;

class FolioConfig extends Model
{
    use BelongsToEmpresa;

    protected $table = 'folio_configs';

    protected $fillable = [
        'document_type',
        'prefix',
        'current_number',
        'padding',
    ];
}
