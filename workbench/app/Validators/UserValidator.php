<?php

namespace Workbench\App\Validators;

use Luminix\Backend\Validation\Validator;

class UserValidator extends Validator
{
    public function store(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function update(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $this->parent->id,
            'password' => 'nullable|string|min:8|confirmed',
        ];
    }
}