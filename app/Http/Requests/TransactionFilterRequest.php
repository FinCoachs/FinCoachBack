<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TransactionFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categorie_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('user_id', Auth::id()),
            ],
            'type'   => 'nullable|in:depense,revenu',
            'search' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'categorie_id.exists' => 'La catégorie sélectionnée est invalide ou ne vous appartient pas',
            'type.in'             => 'Le type doit être "depense" ou "revenu"',
        ];
    }
}
