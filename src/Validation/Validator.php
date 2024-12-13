<?php

namespace Luminix\Backend\Validation;

use Illuminate\Database\Eloquent\Model;

abstract class Validator {

    final public function __construct(
        protected Model $parent,
    )
    {}

    public function getValidationRules(string $for): array
    {
        if (method_exists($this, $for)) {
            return call_user_func([$this, $for]);
        }
        return [];
    }

}