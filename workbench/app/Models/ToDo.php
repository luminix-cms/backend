<?php

namespace Workbench\App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Luminix\Backend\Model\LuminixModel;

class ToDo extends Model
{
    use HasFactory, LuminixModel;

    protected $labeledBy = 'title';

    protected $fillable = [
        'description',
        'title',
        'completed',
        'user_id',
    ];

    protected $syncs = [
        'categories',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function getValidationRules(string $for): array
    {
        return match ($for) {
            'store' => [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
            ],
            'update' => [
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
            ],
            default => [],
        };
    }

    public function scopeAllowed(Builder $query, string $permission)
    {
        $query->where('user_id', auth()->id());
    }
}
