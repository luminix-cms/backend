<?php

namespace Workbench\App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel, SoftDeletes;

    protected $fillable = ['title', 'body', 'notes', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Permission strings received by scopeAllowed, recorded for contract tests. */
    public static array $receivedPermissions = [];

    public function scopeAllowed(Builder $query, string $permission): void
    {
        static::$receivedPermissions[] = $permission;

        if (in_array($permission, ['update', 'delete'])) {
            $query->where('user_id', auth()->id());
        }
    }
}
