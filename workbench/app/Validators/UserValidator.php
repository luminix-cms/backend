<?php

namespace Workbench\App\Validators;

use Luminix\Backend\Validation\Validator;
use Workbench\App\Models\User;

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

    public function update(User $user): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ];
    }
}