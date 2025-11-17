<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'x-user-id' => 'required|integer|exists:users,id',
        ];
    }

    public function validationData(): array
    {
        return collect($this->headers->all())
            ->map(fn($v) => $v[0])
            ->toArray();
    }
}
