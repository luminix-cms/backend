<?php

namespace Workbench\App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Workbench\App\Models\Tag;

trait HasTags {

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

}