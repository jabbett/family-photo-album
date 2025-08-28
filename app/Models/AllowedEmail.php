<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllowedEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Check if an email is allowed to register
     */
    public static function isAllowed(string $email): bool
    {
        return static::where('email', $email)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all active allowed emails
     */
    public static function getActiveEmails()
    {
        return static::where('is_active', true)->get();
    }
}
