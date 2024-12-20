<?php

namespace Luminix\Backend\Validation;

use Attribute;
use Illuminate\Database\Eloquent\Model;

#[Attribute(Attribute::TARGET_CLASS)]
class WithValidator
{
    public function __construct(
        public string $validatorClass
    ) {}


    public function getValidator(): Validator
    {
        return new $this->validatorClass();
    }

}