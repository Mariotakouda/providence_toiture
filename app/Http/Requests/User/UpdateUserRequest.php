<?php

namespace App\Http\Requests\User;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(RoleName::class), function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === RoleName::Admin->value && ! $this->user()?->hasRole(RoleName::Admin->value)) {
                    $fail('Seul un administrateur peut attribuer le rôle administrateur.');
                }
            }],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'email' => 'e-mail',
            'phone' => 'téléphone',
            'role' => 'rôle',
            'password' => 'mot de passe',
        ];
    }
}
