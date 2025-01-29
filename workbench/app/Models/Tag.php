<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Luminix\Backend\Model\LuminixModel;

class Tag extends Model
{

    use LuminixModel;

    protected function toDos(): MorphToMany
    {
        return $this->morphedByMany(ToDo::class, 'taggable');
    }

    protected function categories(): MorphToMany
    {
        return $this->morphedByMany(Category::class, 'taggable');
    }

    protected function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'taggable');
    }
}