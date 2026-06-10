<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

use App\Models\Concerns\BelongsToEmpresa;

class TodoAttachment extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'todo_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    protected $appends = ['url'];

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
