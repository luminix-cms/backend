<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Luminix\Backend\Model\LuminixModel;

class User extends Authenticatable
{
    use HasFactory, Notifiable, LuminixModel;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function toDos(): HasMany
    {
        return $this->hasMany(ToDo::class);
    }

    public function getValidationRules(string $for): array
    {
        return match ($for) {
            'store' => [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ],
            'update' => [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $this->id,
                'password' => 'nullable|string|min:8|confirmed',
            ],
            default => [],
        };
    }
}
