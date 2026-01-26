<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterTrack extends Model
{
    protected $fillable = [
        'blog_post_id',
        'cliente_id',
        'token',
        'abierto_at',
        'clic_at',
        'interes_at',
        'enviado_at',
    ];

    public function blogPost()
    {
        return $this->belongsTo(BlogPost::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
