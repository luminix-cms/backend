<?php

namespace Luminix\Backend\Validation;

use Illuminate\Database\Eloquent\Model;

abstract class Validator {

    final public function __construct()
    {}

    final public function getValidationRules(string $for, Model $item): array
    {
        if (method_exists($this, $for)) {
            return call_user_func([$this, $for], $item);
        }
        return [];
    }

}