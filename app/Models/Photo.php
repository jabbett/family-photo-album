<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'position',
        'original_path',
        'thumbnail_path',
        'width',
        'height',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function getOriginalUrlAttribute(): string
    {
        // Use a relative public path so it works regardless of APP_URL or port
        return '/storage/' . ltrim($this->original_path, '/');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? ('/storage/' . ltrim($this->thumbnail_path, '/')) : null;
    }
}


