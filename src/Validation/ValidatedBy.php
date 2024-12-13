<?php

namespace Luminix\Backend\Validation;

use Attribute;
use Illuminate\Database\Eloquent\Model;

#[Attribute(Attribute::TARGET_CLASS)]
class ValidatedBy
{
    public function __construct(
        public string $validatorClass
    ) {}


    public function getValidator(Model $parent): Validator
    {
        return new $this->validatorClass($parent);
    }

}