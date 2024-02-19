<?php

namespace Luminix\Backend\Model;

use Illuminate\Support\Str;

trait HasIndentifiers
{

    static function getAlias(): string
    {
        return Str::snake(class_basename(static::class));
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
