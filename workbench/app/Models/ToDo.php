<?php

namespace Workbench\App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Luminix\Backend\Controllers\WithController;
use Luminix\Backend\Model\LuminixModel;
use Workbench\App\Contracts\HasTags;
use Workbench\App\Http\Controllers\ToDoController;

#[WithController(ToDoController::class)]
class ToDo extends Model
{
    use HasFactory, LuminixModel, HasTags;

    protected $labeledBy = 'title';

    protected $fillable = [
        'description',
        'title',
        'completed',
        'user_id',
    ];

    protected $syncs = [
        'categories',
        'tags',
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

    /** Permission strings received by scopeAllowed, recorded for contract tests. */
    public static array $receivedPermissions = [];

    public function scopeAllowed(Builder $query, string $permission)
    {
        static::$receivedPermissions[] = $permission;

        $query->where('user_id', auth()->id());
    }
}
