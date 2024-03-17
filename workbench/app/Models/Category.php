<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Luminix\Backend\Model\LuminixModel;

class Category extends Model
{
    use HasFactory, LuminixModel;

    protected $fillable = [
        'name',
    ];

    public function toDos(): BelongsToMany
    {
        return $this->belongsToMany(ToDo::class);
    }
}
