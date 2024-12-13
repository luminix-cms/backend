<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Luminix\Backend\Model\LuminixModel;
use Luminix\Backend\Validation\ValidatedBy;
use Workbench\App\Validators\UserValidator;

#[ValidatedBy(UserValidator::class)]
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

}
