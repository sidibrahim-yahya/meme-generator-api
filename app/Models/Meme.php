<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meme extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'image_path',
        'text_top',
        'text_bottom',
        'font_size',
        'font_color',
        'stroke_color',
        'stroke_width',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtenir l'URL complète de l'image
     */
    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }

    /**
     * Scope pour les mèmes récents
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope pour les mèmes d'un utilisateur
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
} 