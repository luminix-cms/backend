<?php

namespace Luminix\Backend\Model;

use Illuminate\Support\Str;

trait HasIdentifiers
{

    static function getAlias(): string
    {
        return Str::snake(class_basename(static::class));
    }


    static function getDisplayName(): array
    {
        return [
            'singular' => Str::title(Str::snake(Str::singular(class_basename(static::class)), ' ')),
            'plural' => Str::title(Str::snake(Str::plural(class_basename(static::class)), ' ')),
        ];
    }


    /**
     * Get the key for the model's label.
     * 
     * @return string
     */
    public function getLabel(): string
    {
        if ($this->labeledBy ?? false) {
            return $this->labeledBy;
        }

        return $this->fillable[0];
    }
}
